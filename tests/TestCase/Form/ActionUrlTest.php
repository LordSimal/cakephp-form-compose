<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

/** Rejects unroutable action targets before opening or signing a form. */
class ActionUrlTest extends FormTestCase
{
    public function testAnInvalidExternalPortCannotBecomeASignedFormAction(): void
    {
        $form = $this->helper()->form()->url('http://example.test:99999')->bind();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid form action URL.');
        $form->open();
    }
}
