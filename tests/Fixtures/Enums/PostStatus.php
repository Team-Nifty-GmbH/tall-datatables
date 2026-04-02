<?php

namespace Tests\Fixtures\Enums;

enum PostStatus: string
{
    case Archived = 'archived';
    case Draft = 'draft';
    case Published = 'published';
}
