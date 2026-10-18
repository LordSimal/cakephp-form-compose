<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Configuration;

use FormCompose\Configuration\ChoiceGroupConfig;
use FormCompose\Test\FormTestCase;

/** Ensures replacement group choices remain immutable and reach rendered selects. */
class ChoiceGroupConfigTest extends FormTestCase
{
    public function testReplacingChoicesKeepsTheReusableGroupIndependent(): void
    {
        $original = ChoiceGroupConfig::builder('Currencies', ['EUR' => 'Euro']);
        $replacement = $original->choices(['USD' => 'US dollar']);
        $form = $this->helper()->form()->bind();
        $html =
            $form->open()
            . $form->control('original')->choices([$original])->render()
            . $form->control('replacement')->choices([$replacement])->render()
            . $form->close();
        static::assertSame(
            1.0,
            $this->xpath($html)->evaluate('count(//select[@name="original"]//option[@value="EUR"])'),
        );
        static::assertSame(
            0.0,
            $this->xpath($html)->evaluate('count(//select[@name="original"]//option[@value="USD"])'),
        );
        static::assertSame(
            1.0,
            $this->xpath($html)->evaluate('count(//select[@name="replacement"]//option[@value="USD"])'),
        );
        static::assertSame(
            'US dollar',
            $this->xpath($html)->evaluate('string(//select[@name="replacement"]//option[@value="USD"])'),
        );
    }
}
