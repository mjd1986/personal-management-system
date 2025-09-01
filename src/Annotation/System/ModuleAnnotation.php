<?php

namespace App\Annotation\System;

use Attribute;

/**
 * This annotation handles checking if given method should be called, by ensuring that it's not relating to locked
 * resource
 *
 * This is especially helpful to control access to the action methods / classes
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ModuleAnnotation
{
    const ATTRIBUTE_KEY_NAME            = "name";
    const ATTRIBUTE_KEY_RELATED_MODULES = "relatedModules";

    /**
     * @var string $name
     */
    private string $name = "";

    /**
     * @var array $relatedModules
     */
    private array $relatedModules = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getRelatedModules()
    {
        return $this->relatedModules;
    }

    /**
     * @param array $relatedModules
     */
    public function setRelatedModules($relatedModules): void
    {
        $this->relatedModules = $relatedModules;
    }

    /**
     * LockedResource constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->name           = $values[self::ATTRIBUTE_KEY_NAME] ?? "";
        $this->relatedModules = $values[self::ATTRIBUTE_KEY_RELATED_MODULES] ?? [];
    }

}