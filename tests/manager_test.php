<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tiny_elements;

use advanced_testcase;
use tiny_elements\local\constants;
use tiny_elements\manager;

/**
 * Class manager_test
 *
 * @package    tiny_elements
 * @copyright  2025 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiny_elements\manager
 */
final class manager_test extends advanced_testcase {
    /**
     * Create a test set.
     *
     * @param manager $manager
     * @return array
     */
    public function create_items(manager $manager): array {
        $draftitemid = file_get_unused_draft_itemid();
        file_prepare_draft_area($draftitemid, $manager->get_contextid(), 'tiny_elements', 'images', 0, constants::FILE_OPTIONS);

        $categoryid = $manager->add_compcat((object)[
            'name' => 'test',
            'displayname' => 'test',
            'css' => '',
            'compcatfiles' => $draftitemid,
        ]);
        $category2id = $manager->add_compcat((object)[
            'name' => 'test2',
            'displayname' => 'test2',
            'css' => '',
            'compcatfiles' => $draftitemid,
        ]);

        $flavorid = $manager->add_flavor((object)[
            'name' => 'testflavor',
            'displayname' => 'testflavor',
            'css' => '',
            'iconurl' => '',
        ]);
        $flavor2id = $manager->add_flavor((object)[
            'name' => 'testflavor2',
            'displayname' => 'testflavor2',
            'css' => '',
            'iconurl' => '',
        ]);

        $variantid = $manager->add_variant((object)[
            'name' => 'testvariant',
            'displayname' => 'testvariant',
            'css' => '',
            'iconurl' => '',
        ]);
        $variant2id = $manager->add_variant((object)[
            'name' => 'testvariant2',
            'displayname' => 'testvariant2',
            'css' => '',
            'iconurl' => '',
        ]);

        $componentid = $manager->add_component((object)[
            'name' => 'testcomponent',
            'displayname' => 'testcomponent',
            'css' => '',
            'js' => '',
            'iconurl' => '',
            'flavors' => ['testflavor'],
            'variants' => ['testvariant', 'testvariant2'],
            'categoryname' => 'test',
        ]);
        $component2id = $manager->add_component((object)[
            'name' => 'testcomponent2',
            'displayname' => 'testcomponent2',
            'css' => '',
            'js' => '',
            'iconurl' => '',
            'flavors' => ['testflavor', 'testflavor2'],
            'variants' => ['testvariant'],
            'categoryname' => 'test2',
        ]);

        return [
            'categoryid' => $categoryid,
            'category2id' => $category2id,
            'flavorid' => $flavorid,
            'flavor2id' => $flavor2id,
            'componentid' => $componentid,
            'component2id' => $component2id,
            'variantid' => $variantid,
            'variant2id' => $variant2id,
        ];
    }

    /**
     * Test delete_compcat method.
     */
    public function test_delete_compcat(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        // Delete the category.
        $manager->delete_compcat($data['categoryid']);

        $this->assertFalse($DB->record_exists('tiny_elements_compcat', ['id' => $data['categoryid']]));
        $this->assertFalse($DB->record_exists('tiny_elements_component', ['id' => $data['componentid']]));
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent', 'flavorname' => 'testflavor'])
        );
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent', 'variant' => 'testvariant'])
        );
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent', 'variant' => 'testvariant2'])
        );

        $this->assertTrue($DB->record_exists('tiny_elements_compcat', ['id' => $data['category2id']]));
        $this->assertTrue($DB->record_exists('tiny_elements_component', ['id' => $data['component2id']]));
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent2', 'flavorname' => 'testflavor'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent2', 'flavorname' => 'testflavor2'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent2', 'variant' => 'testvariant'])
        );
    }


    /**
     * Test delete_flavor method.
     */
    public function test_delete_flavor(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        // Delete one flavor.
        $manager->delete_flavor($data['flavorid']);

        // Verify the flavor is deleted.
        $this->assertFalse($DB->record_exists('tiny_elements_flavor', ['id' => $data['flavorid']]));
        $this->assertFalse($DB->record_exists('tiny_elements_comp_flavor', ['flavorname' => 'testflavor']));

        // Verify the other flavor is not deleted.
        $this->assertTrue($DB->record_exists('tiny_elements_flavor', ['id' => $data['flavor2id']]));
        $this->assertTrue($DB->record_exists('tiny_elements_comp_flavor', ['flavorname' => 'testflavor2']));
    }

    /**
     * Test delete_variant method.
     */
    public function test_delete_variant(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        // Delete one variant.
        $manager->delete_variant($data['variantid']);

        // Verify the variant is deleted.
        $this->assertFalse($DB->record_exists('tiny_elements_variant', ['id' => $data['variantid']]));
        $this->assertFalse($DB->record_exists('tiny_elements_comp_variant', ['variant' => 'testvariant']));

        // Verify the other variant is not deleted.
        $this->assertTrue($DB->record_exists('tiny_elements_variant', ['id' => $data['variant2id']]));
        $this->assertTrue($DB->record_exists('tiny_elements_comp_variant', ['variant' => 'testvariant2']));
    }

    /**
     * Test delete_component method.
     */
    public function test_delete_component(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        // Delete the component.
        $manager->delete_component($data['componentid']);

        // Verify the component is deleted.
        $this->assertFalse($DB->record_exists('tiny_elements_component', ['id' => $data['componentid']]));
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent', 'flavorname' => 'testflavor'])
        );
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent', 'variant' => 'testvariant'])
        );
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent', 'variant' => 'testvariant2'])
        );

        // Verify the other component is not deleted.
        $this->assertTrue($DB->record_exists('tiny_elements_component', ['id' => $data['component2id']]));
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent2', 'flavorname' => 'testflavor'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent2', 'flavorname' => 'testflavor2'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent2', 'variant' => 'testvariant'])
        );
    }

    /**
     * Test add_compcat method.
     *
     * @return void
     */
    public function test_add_compcat(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $draftitemid = file_get_unused_draft_itemid();
        file_prepare_draft_area($draftitemid, $manager->get_contextid(), 'tiny_elements', 'images', 0, constants::FILE_OPTIONS);

        $categoryid = $manager->add_compcat((object)[
            'name' => 'test',
            'displayname' => 'test',
            'css' => '',
            'compcatfiles' => $draftitemid,
        ]);

        $this->assertTrue($DB->record_exists('tiny_elements_compcat', ['id' => $categoryid]));
    }

    /**
     * Test add_flavor method.
     *
     * @return void
     */
    public function test_add_flavor(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $flavorid = $manager->add_flavor((object)[
            'name' => 'testflavor',
            'displayname' => 'testflavor',
            'css' => '',
            'iconurl' => '',
        ]);

        $this->assertTrue($DB->record_exists('tiny_elements_flavor', ['id' => $flavorid]));
    }

    /**
     * Test add_variant method.
     *
     * @return void
     */
    public function test_add_variant(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $variantid = $manager->add_variant((object)[
            'name' => 'testvariant',
            'displayname' => 'testvariant',
            'css' => '',
            'iconurl' => '',
        ]);

        $this->assertTrue($DB->record_exists('tiny_elements_variant', ['id' => $variantid]));
    }

    /**
     * Test add_component method.
     *
     * @return void
     */
    public function test_add_component(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        $this->assertTrue($DB->record_exists('tiny_elements_component', ['id' => $data['componentid']]));
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'testcomponent', 'flavorname' => 'testflavor'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent', 'variant' => 'testvariant'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'testcomponent', 'variant' => 'testvariant2'])
        );
    }

    /**
     * Test update_compcat method.
     */
    public function test_update_compcat(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        $draftitemid = file_get_unused_draft_itemid();
        file_prepare_draft_area($draftitemid, $manager->get_contextid(), 'tiny_elements', 'images', 0, constants::FILE_OPTIONS);

        $manager->update_compcat((object)[
            'id' => $data['categoryid'],
            'name' => 'changedname',
            'displayname' => 'changeddisplayname',
            'css' => '',
            'compcatfiles' => $draftitemid,
        ]);

        $category = $DB->get_record('tiny_elements_compcat', ['id' => $data['categoryid']]);
        $this->assertEquals('changedname', $category->name);
        $this->assertEquals('changeddisplayname', $category->displayname);
        $category2 = $DB->get_record('tiny_elements_compcat', ['id' => $data['category2id']]);
        $this->assertEquals('test2', $category2->name);
        $this->assertEquals('test2', $category2->displayname);
    }

    /**
     * Test update_flavor method.
     */
    public function test_update_flavor(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        $manager->update_flavor((object)[
            'id' => $data['flavorid'],
            'name' => 'changedname',
            'displayname' => 'changeddisplayname',
            'css' => '',
        ]);

        $flavor = $DB->get_record('tiny_elements_flavor', ['id' => $data['flavorid']]);
        $this->assertEquals('changedname', $flavor->name);
        $this->assertEquals('changeddisplayname', $flavor->displayname);
        $flavor2 = $DB->get_record('tiny_elements_flavor', ['id' => $data['flavor2id']]);
        $this->assertEquals('testflavor2', $flavor2->name);
        $this->assertEquals('testflavor2', $flavor2->displayname);
    }

    /**
     * Test update_variant method.
     */
    public function test_update_variant(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        $manager->update_variant((object)[
            'id' => $data['variantid'],
            'name' => 'changedname',
            'displayname' => 'changeddisplayname',
            'css' => '',
            'iconurl' => '',
        ]);

        $variant = $DB->get_record('tiny_elements_variant', ['id' => $data['variantid']]);
        $this->assertEquals('changedname', $variant->name);
        $this->assertEquals('changeddisplayname', $variant->displayname);
        $variant2 = $DB->get_record('tiny_elements_variant', ['id' => $data['variant2id']]);
        $this->assertEquals('testvariant2', $variant2->name);
        $this->assertEquals('testvariant2', $variant2->displayname);
    }

    /**
     * Test update_component method.
     */
    public function test_update_component(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $contextid = 1;
        $manager = new manager($contextid);

        $data = $this->create_items($manager);

        $manager->update_component((object)[
            'id' => $data['componentid'],
            'name' => 'changedname',
            'displayname' => 'changeddisplayname',
            'css' => '',
            'js' => '',
            'iconurl' => '',
            'flavors' => ['testflavor2'],
            'variants' => ['testvariant'],
            'categoryname' => 'testcategory2',
        ]);

        $component = $DB->get_record('tiny_elements_component', ['id' => $data['componentid']]);
        $this->assertEquals('changedname', $component->name);
        $this->assertEquals('changeddisplayname', $component->displayname);
        $this->assertEquals('testcategory2', $component->categoryname);
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'changedname', 'flavorname' => 'testflavor2'])
        );
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_flavor', ['componentname' => 'changedname', 'flavorname' => 'testflavor'])
        );
        $this->assertTrue(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'changedname', 'variant' => 'testvariant'])
        );
        $this->assertFalse(
            $DB->record_exists('tiny_elements_comp_variant', ['componentname' => 'changedname', 'variant' => 'testvariant2'])
        );
    }
}
