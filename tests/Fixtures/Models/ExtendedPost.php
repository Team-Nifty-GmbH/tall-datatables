<?php

namespace Tests\Fixtures\Models;

/**
 * A subclass bound over its base class in the container, which is how a
 * customised core model reaches a data table.
 */
class ExtendedPost extends Post
{
    protected $table = 'posts';
}
