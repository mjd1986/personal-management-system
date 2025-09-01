<?php

namespace App\Action\Modules\System;

use App\Annotation\System\ModuleAnnotation;
use App\Controller\Modules\ModulesController;
use App\Entity\FilesTags;
use App\Entity\Modules\Notes\MyNotes;
use App\Enum\StorageModuleEnum;
use App\Response\Base\BaseResponse;
use App\Services\Module\Storage\StorageService;
use App\Services\TypeProcessor\ArrayHandler;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/module/system/search", name: "module.system.search.")]
#[ModuleAnnotation(values: ["name" => ModulesController::MODULE_NAME_SYSTEM])]
class SearchAction extends AbstractController {

    public function __construct(
        private readonly StorageService $storageService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route("/all", name: "get_all", methods: [Request::METHOD_GET])]
    public function getAll(Request $request): JsonResponse
    {
        $query = $request->query->get('query');
        if (empty($query)) {
            throw new LogicException("Query cannot be empty");
        }

        $entriesData = [];
        $entriesData = $this->searchInStorage($query, $entriesData);
        $entriesData = $this->searchInNotes($query, $entriesData);

        $response = BaseResponse::buildOkResponse();
        $response->setAllRecordsData($entriesData);

        return $response->toJsonResponse();
    }

    /**
     * @param string $query
     * @param array  $entriesData
     *
     * @return array[]
     * @throws Exception
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function searchInStorage(string $query, array $entriesData): array
    {
        $queryParts     = explode(" ", $query);
        $cartesianQuery = [];
        ArrayHandler::cartesianProduct(input: $queryParts, result: $cartesianQuery, depth: 2, stringBetween: " ");

        $queryTagMatchSet = array_merge($queryParts, $cartesianQuery);
        foreach (StorageModuleEnum::cases() as $enum) {
            $matchingFiles = [];
            $files         = [];

            $this->storageService->getTreeData($enum, $files);
            foreach ($files as $fileData) {
                $fileName = $fileData['name'] . (!empty($fileData['ext']) ? '.' . $fileData['ext'] : '');

                $fullPath  = $fileData['dir'] . DIRECTORY_SEPARATOR . $fileName;
                $tagEntity = $this->entityManager->getRepository(FilesTags::class)->getFileTagsEntityByFileFullPath($fullPath);
                $hasTag    = false;

                if (!is_null($tagEntity)) {
                    $hasTag = $tagEntity->isAnyTagMatching($queryTagMatchSet);
                }

                if (!str_contains(strtolower($fileName), strtolower($query)) && !$hasTag) {
                    continue;
                }

                $matchingFiles[] = [
                    'name'        => $fileName,
                    'identifiers' => [
                        basename($fileData['dir']),
                        $fileData['dir'],
                    ],
                ];
            }

            $entriesData[$enum->value] = $matchingFiles;
        }

        return $entriesData;
    }

    /**
     * @param string $query
     * @param array  $entriesData
     *
     * @return array
     */
    private function searchInNotes(string $query, array $entriesData): array
    {
        $notes = $this->entityManager->getRepository(MyNotes::class)->findByTitle($query);

        $notesData = [];
        foreach ($notes as $note) {
            $notesData[] = [
                'name'        => $note->getTitle(),
                'identifiers' => [
                    $note->getCategory()?->getId(),
                ],
            ];
        }

        $entriesData['notes'] = $notesData;

        return $entriesData;
    }

}