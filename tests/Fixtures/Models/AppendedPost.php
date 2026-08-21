<?php

namespace Tests\Fixtures\Models;

class AppendedPost extends Post
{
    protected $appends = ['expensive'];

    protected $table = 'posts';
}
