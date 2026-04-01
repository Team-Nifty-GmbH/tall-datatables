<?php

namespace Tests\Fixtures\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
