<?php

namespace TeamNiftyGmbH\DataTable\Contracts;

interface InteractsWithDataTables
{
    /**
     * This should a short string that describes a single model.
     * For example:
     * An address model would return something like "John Doe".
     * return $this->first_name . ' ' . $this->last_name;
     */
    public function getLabel(): ?string;

    /**
     * This should return a short description of the model.
     * For example:
     * An address model would return something like "John Doe, 123 Main Street, 12345 New York".
     * return $this->first_name . ' ' . $this->last_name . ', ' . $this->street . ', ' . $this->zip . ' ' . $this->city;
     */
    public function getDescription(): ?string;

    /**
     * This should return a url to the detail page of the model.
     */
    public function getUrl(): ?string;

    /**
     * This should return a url to the avatar of the model.
     * Can be a icon url, see the icons route.
     */
    public function getAvatarUrl(): ?string;
}
