<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use Tests\Models\User;
use Tests\TestCase;

class ModelInfoTest extends TestCase
{
    #[Test]
    public function it_caches_model_info(): void
    {
        // Clear cache before test
        Cache::forget(config('tall-datatables.cache_key') . '.modelInfo');

        // First call should cache the result
        $modelInfo1 = ModelInfo::forModel(User::class);

        // Check that cache exists
        $this->assertNotNull(Cache::get(config('tall-datatables.cache_key') . '.modelInfo'));

        // Second call should use the cache
        $modelInfo2 = ModelInfo::forModel(User::class);

        // They should be the exact same object
        $this->assertEquals($modelInfo1, $modelInfo2);
    }

    #[Test]
    public function it_can_get_all_model_info(): void
    {
        $modelInfos = ModelInfo::forAllModels();

        $this->assertNotEmpty($modelInfos);
        $this->assertTrue($modelInfos->contains(fn ($info) => $info->class === User::class));
    }

    #[Test]
    public function it_can_get_model_attributes(): void
    {
        $modelInfo = ModelInfo::forModel(User::class);

        $this->assertNotNull($modelInfo->attributes);
        $this->assertTrue($modelInfo->attributes->contains('name', 'id'));
        $this->assertTrue($modelInfo->attributes->contains('name', 'name'));
        $this->assertTrue($modelInfo->attributes->contains('name', 'email'));
    }

    #[Test]
    public function it_can_get_model_info_for_a_model(): void
    {
        $modelInfo = ModelInfo::forModel(User::class);

        $this->assertNotNull($modelInfo);
        $this->assertEquals(User::class, $modelInfo->class);
        $this->assertEquals('users', $modelInfo->table);
    }

    #[Test]
    public function it_can_get_model_relations(): void
    {
        $modelInfo = ModelInfo::forModel(User::class);

        // The User model might not have any relations, but we can test that the relations collection exists
        $this->assertNotNull($modelInfo->relations);
    }
}
