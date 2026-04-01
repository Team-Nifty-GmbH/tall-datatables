<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Traits\HasFrontendAttributes;

class CustomRoutablePost extends Model implements InteractsWithDataTables
{
    use HasFrontendAttributes;

    protected ?string $detailRouteName = 'posts.show';

    protected $guarded = ['id'];

    protected $table = 'posts';

    public function detailRouteParams(): array
    {
        return ['slug' => 'custom-slug'];
    }

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
