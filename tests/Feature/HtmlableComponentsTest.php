<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableRowAttributes;
use Tests\TestCase;

class HtmlableComponentsTest extends TestCase
{
    #[Test]
    public function it_can_conditionally_render_button(): void
    {
        // Button that should render
        $button1 = DataTableButton::make()
            ->text('Visible')
            ->when(true);

        // Button that should not render
        $button2 = DataTableButton::make()
            ->text('Hidden')
            ->when(false);

        $this->assertNotNull($button1->toHtml());
        $this->assertNull($button2->toHtml());
    }

    #[Test]
    public function it_can_create_button_with_custom_attributes(): void
    {
        $button = DataTableButton::make()
            ->text('Custom')
            ->attributes([
                'id' => 'custom-button',
                'data-action' => 'test',
                'x-on:click' => 'doSomething()',
            ]);

        $html = $button->toHtml();

        $this->assertStringContainsString('id="custom-button"', $html);
        $this->assertStringContainsString('data-action="test"', $html);
        $this->assertStringContainsString('x-on:click="doSomething()"', $html);
    }

    #[Test]
    public function it_can_create_button_with_default_config(): void
    {
        $button = DataTableButton::make()
            ->text('Test Button')
            ->color('indigo');

        $html = $button->toHtml();

        $this->assertStringContainsString('Test Button', $html);
        $this->assertStringContainsString('indigo', $html);
    }

    #[Test]
    public function it_can_create_button_with_icons(): void
    {
        $button = DataTableButton::make()
            ->icon('pencil')
            ->text('Edit');

        $html = $button->toHtml();

        $this->assertStringContainsString('Edit', $html);
        $this->assertStringContainsString('pencil', $html);
    }

    #[Test]
    public function it_can_create_circle_button(): void
    {
        $button = DataTableButton::make()
            ->circle()
            ->icon('trash')
            ->color('red');

        $html = $button->toHtml();

        $this->assertStringContainsString('trash', $html);
        $this->assertStringContainsString('red', $html);
    }

    #[Test]
    public function it_can_create_row_attributes(): void
    {
        $attributes = DataTableRowAttributes::make()
            ->class('row-class')
            ->bind('class', 'record.active ? "active-row" : "inactive-row"')
            ->on('click', 'handleClick($event)')
            ->merge(['data-id' => '123']);

        $this->assertEquals('row-class', $attributes->get('class'));
        $this->assertEquals('record.active ? "active-row" : "inactive-row"', $attributes->get('x-bind:class'));
        $this->assertEquals('handleClick($event)', $attributes->get('x-on:click'));
        $this->assertEquals('123', $attributes->get('data-id'));
    }
}
