<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Traits\HasFrontendAttributes;

class RoutablePost extends Model implements InteractsWithDataTables
{
    use HasFrontendAttributes;

    protected $table = 'posts';

    protected $guarded = ['id'];

    protected ?string $detailRouteName = 'posts.show';

    public function getAvatarUrl(): ?string
    {
        return null;
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getLabel(): ?string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return '/posts/' . $this->getKey();
    }
}
