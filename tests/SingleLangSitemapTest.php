<?php

namespace cebe\luya\sitemap\tests;

use cebe\luya\sitemap\tests\Setup;
use luya\cms\models\Config;
use cebe\luya\sitemap\Module;
use cebe\luya\sitemap\controllers\SitemapController;
use luya\testsuite\fixtures\ActiveRecordFixture;
use luya\cms\models\NavItem;
use luya\cms\models\Nav;
use luya\admin\models\Lang;
use yii\helpers\FileHelper;

class SingleLangSitemapTest extends Setup
{
    public function getConfigArray()
    {
        return [
            'id' => 'mytestapp',
            'basePath' => dirname(__DIR__),
            'aliases' => [
                'runtime' => dirname(__DIR__) . '/tests/runtime',
            ],
            'modules' => [
                'cms' => 'luya\cms\frontend\Module',
            ],
            'components' => [
                 'db' => [
                     'class' => 'yii\db\Connection',
                     'dsn' => 'sqlite::memory:',
                 ],
                 'request' => [
                     'hostInfo' => 'https://luya.io',
                 ],
                 'adminLanguage' => [
                     'class' => \luya\admin\components\AdminLanguage::class,
                 ],
            ]
        ];
    }

    public function boolProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider boolProvider
     */
    public function testIgnoreHiddenModuleProperty($withHidden)
    {
        $module = new Module('sitemap');
        $module->module = $this->app;
        $module->withHidden = $withHidden;

        static::prepareBasicTableStructureAndData();

        $ctrl = new SitemapController('sitemap', $module);
        $response = $ctrl->actionIndex();
        list($handle, $begin, $end) = $response->stream;

        fseek($handle, $begin);
        $content = stream_get_contents($handle);

        // page should not contain alternative links when only one language is used
        // <xhtml:link rel="alternate" hreflang="en" href="https://luya.io/publish-check-present"/>
        $this->assertNotContains('rel="alternate"', $content);

        $this->assertContainsTrimmed('<loc>https://luya.io</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo</loc>', $content);

        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3/foo-4-child</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/foo-3/foo-4-child/foo-5-child-child</loc>', $content);

        if ($withHidden) {
            // $module->withHidden = true; = 2 Pages in index
            $this->assertContainsTrimmed('<loc>https://luya.io/foo-hidden</loc>', $content);
        } else {
            // $module->withHidden = false; = 1 Page in index
            $this->assertNotContains('<loc>https://luya.io/foo-hidden</loc>', $content);
        }

        $this->assertNotContains('<loc>https://luya.io/not-to-show-404</loc>', $content);

        $this->assertNotContains('<loc>https://luya.io/publish-check-past</loc>', $content);
        $this->assertNotContains('<loc>https://luya.io/publish-check-future</loc>', $content);
        $this->assertContainsTrimmed('<loc>https://luya.io/publish-check-present</loc>', $content);
    }

    public static function prepareBasicTableStructureAndData()
    {
        $navItemFixture = (new ActiveRecordFixture([
            'modelClass' => NavItem::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'nav_id' => 1,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo',
                    'title' => 'Bar',
                ],
                'model2' => [
                    'id' => 2,
                    'nav_id' => 2,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-hidden',
                    'title' => 'Bar Hidden',
                ],
                'model3' => [
                    'id' => 3,
                    'nav_id' => 3,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-3',
                    'title' => 'Bar 3 title',
                ],
                'model4' => [
                    'id' => 4,
                    'nav_id' => 4,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-4-child',
                    'title' => 'Bar 4 child-title',
                ],
                'model5' => [
                    'id' => 5,
                    'nav_id' => 5,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'foo-5-child-child',
                    'title' => 'Bar 5 child child title',
                ],
                'model6' => [
                    'id' => 6,
                    'nav_id' => 6,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'not-to-show-404',
                    'title' => 'Not To Show - 404 - in sitemap',
                ],
                'model7' => [
                    'id' => 9,
                    'nav_id' => 7,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'publish-check-past',
                    'title' => 'Publish Checked Past',
                ],
                'model8' => [
                    'id' => 10,
                    'nav_id' => 8,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'publish-check-present',
                    'title' => 'Publish Checked Present',
                ],
                'model9' => [
                    'id' => 11,
                    'nav_id' => 9,
                    'lang_id' => 1,
                    'timestamp_create' => time(),
                    'timestamp_update' => time(),
                    'alias' => 'publish-check-future',
                    'title' => 'Publish Checked Future',
                ],
            ]
        ]));

        $navFixture = (new ActiveRecordFixture([
            'modelClass' => Nav::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model2' => [
                    'id' => 2,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 1,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model3' => [
                    'id' => 3,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model4' => [
                    'id' => 4,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 3,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model5' => [
                    'id' => 5,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 4,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model6' => [
                    'id' => 6,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                ],
                'model7' => [ // past : current time - some time : should not be in sitemap.xml
                    'id' => 7,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                    'publish_from' => 1528309800, // 07.06.2018
                    'publish_till' => 1544121000, // 07.12.2018
                ],
                'model8' => [ //  current : should be in sitemap.xml
                    'id' => 8,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                    'publish_from' => time(),
                    'publish_till' => time() + 10000,
                ],
                'model9' => [ //  future : should not be in sitemap.xml
                    'id' => 9,
                    'nav_container_id' => 1,
                    'parent_nav_id' => 0,
                    'is_deleted' => 0,
                    'is_hidden' => 0,
                    'is_offline' => 0,
                    'is_draft' => 0,
                    'publish_from' => time() + 20000,
                    'publish_till' => time() + 50000,
                ],
            ]
        ]));

        $langFixture = (new ActiveRecordFixture([
            'modelClass' => Lang::class,
            'fixtureData' => [
                'model1' => [
                    'id' => 1,
                    'name' => 'English',
                    'short_code' => 'en',
                    'is_default' => 1,
                    'is_deleted' => 0,
                ]
            ]
        ]));

        $configFixture = (new ActiveRecordFixture([
            'modelClass' => Config::class,
            'fixtureData' => [
                'model1' => [
                    'name' => 'httpExceptionNavId',
                    'value' => 6,
                ]
            ]
        ]));
    }
}
