<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\TestSuite\TestCase;
use TestApp\Model\Enum\ArticleStatusTrait;
use TestApp\Model\Enum\ArticleStatusTraitLabeled;

/**
 * Tests for EnumLabelTrait.
 */
class EnumLabelTraitTest extends TestCase
{
    /**
     * Test that label() returns a humanized version of the case name when no Label attribute is present.
     */
    public function testLabelWithoutAttribute(): void
    {
        $this->assertSame('Published', ArticleStatusTrait::Published->label());
        $this->assertSame('Unpublished', ArticleStatusTrait::Unpublished->label());
        $this->assertSame('Pending Review', ArticleStatusTrait::PendingReview->label());
    }

    /**
     * Test that label() returns the value from the Label attribute when present.
     */
    public function testLabelWithAttribute(): void
    {
        $this->assertSame('Article is published', ArticleStatusTraitLabeled::Published->label());
        $this->assertSame('Article is not published', ArticleStatusTraitLabeled::Unpublished->label());
    }

    /**
     * Test that label() falls back to a humanized name when the Label attribute is absent,
     * even in an enum that has the attribute on other cases.
     */
    public function testLabelFallbackWithoutAttributeInMixedEnum(): void
    {
        $this->assertSame('Pending Review', ArticleStatusTraitLabeled::PendingReview->label());
    }

    /**
     * Test that label() returns a consistent (cached) result on repeated calls.
     */
    public function testLabelIsCached(): void
    {
        $firstCall = ArticleStatusTrait::Published->label();
        $secondCall = ArticleStatusTrait::Published->label();

        $this->assertSame($firstCall, $secondCall);
    }

    /**
     * Test that label() returns a consistent (cached) result on repeated calls when using the Label attribute.
     */
    public function testLabelWithAttributeIsCached(): void
    {
        $firstCall = ArticleStatusTraitLabeled::Published->label();
        $secondCall = ArticleStatusTraitLabeled::Published->label();

        $this->assertSame($firstCall, $secondCall);
    }
}
