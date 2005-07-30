<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../form.php');
    require_once(dirname(__FILE__) . '/../encoding.php');
    
    class TestOfForm extends UnitTestCase {
        
        function testFormAttributes() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => 'here.php', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual($form->getMethod(), 'get');
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/here.php'));
            $this->assertIdentical($form->getId(), '33');
            $this->assertNull($form->getValue(new SimpleByName('a')));
        }
        
        function testEmptyAction() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => '', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/index.html'));
        }
        
        function testMissingAction() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/index.html'));
        }
        
        function testRootAction() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => '/', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/'));
        }
        
        function testDefaultFrameTargetOnForm() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => 'here.php', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $form->setDefaultTarget('frame');
            
            $expected = new SimpleUrl('http://host/a/here.php');
            $expected->setTarget('frame');
            $this->assertEqual($form->getAction(), $expected);
        }
        
        function testTextWidget() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleTextTag(
                    array('name' => 'me', 'type' => 'text', 'value' => 'Myself')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'Myself');
            $this->assertTrue($form->setField(new SimpleByName('me'), 'Not me'));
            $this->assertFalse($form->setField(new SimpleByName('not_present'), 'Not me'));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'Not me');
            $this->assertNull($form->getValue(new SimpleByName('not_present')));
        }
        
        function testTextWidgetById() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleTextTag(
                    array('name' => 'me', 'type' => 'text', 'value' => 'Myself', 'id' => 50)));
            $this->assertIdentical($form->getValue(new SimpleById(50)), 'Myself');
            $this->assertTrue($form->setField(new SimpleById(50), 'Not me'));
            $this->assertIdentical($form->getValue(new SimpleById(50)), 'Not me');
        }
        
        function testTextWidgetByLabel() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $widget = &new SimpleTextTag(array('name' => 'me', 'type' => 'text', 'value' => 'a'));
            $form->addWidget($widget);
            $widget->setLabel('thing');
            $this->assertIdentical($form->getValue(new SimpleByLabel('thing')), 'a');
            $this->assertTrue($form->setField(new SimpleByLabel('thing'), 'b'));
            $this->assertIdentical($form->getValue(new SimpleByLabel('thing')), 'b');
        }
        
        function testSubmitEmpty() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $this->assertIdentical($form->submit(), new SimpleGetEncoding());
        }
        
        function testSubmitButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go!', 'id' => '9')));
            $this->assertTrue($form->hasSubmit(new SimpleByName('go')));
            $this->assertEqual($form->getValue(new SimpleByName('go')), 'Go!');
            $this->assertEqual($form->getValue(new SimpleById(9)), 'Go!');
            $this->assertEqual(
                    $form->submitButton(new SimpleByName('go')),
                    new SimpleGetEncoding(array('go' => 'Go!')));            
            $this->assertEqual(
                    $form->submitButton(new SimpleByLabel('Go!')),
                    new SimpleGetEncoding(array('go' => 'Go!')));
            $this->assertEqual(
                    $form->submitButton(new SimpleById(9)),
                    new SimpleGetEncoding(array('go' => 'Go!')));            
        }
        
        function testSubmitWithAdditionalParameters() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go!')));
            $this->assertEqual(
                    $form->submitButton(new SimpleByLabel('Go!'), array('a' => 'A')),
                    new SimpleGetEncoding(array('go' => 'Go!', 'a' => 'A')));            
        }
        
        function testSubmitButtonWithLabelOfSubmit() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'test', 'value' => 'Submit')));
            $this->assertEqual(
                    $form->submitButton(new SimpleByName('test')),
                    new SimpleGetEncoding(array('test' => 'Submit')));            
            $this->assertEqual(
                    $form->submitButton(new SimpleByLabel('Submit')),
                    new SimpleGetEncoding(array('test' => 'Submit')));            
        }
        
        function testSubmitButtonWithWhitespacePaddedLabelOfSubmit() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'test', 'value' => ' Submit ')));
            $this->assertEqual(
                    $form->submitButton(new SimpleByLabel('Submit')),
                    new SimpleGetEncoding(array('test' => ' Submit ')));            
        }
        
        function testImageSubmitButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleImageSubmitTag(array(
                    'type' => 'image',
                    'src' => 'source.jpg',
                    'name' => 'go',
                    'alt' => 'Go!',
                    'id' => '9')));
            $this->assertTrue($form->hasImage(new SimpleByLabel('Go!')));
            $this->assertEqual(
                    $form->submitImage(new SimpleByLabel('Go!'), 100, 101),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101)));
            $this->assertTrue($form->hasImage(new SimpleByName('go')));
            $this->assertEqual(
                    $form->submitImage(new SimpleByName('go'), 100, 101),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101)));
            $this->assertTrue($form->hasImage(new SimpleById(9)));
            $this->assertEqual(
                    $form->submitImage(new SimpleById(9), 100, 101),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101)));
        }
        
        function testImageSubmitButtonWithAdditionalData() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleImageSubmitTag(array(
                    'type' => 'image',
                    'src' => 'source.jpg',
                    'name' => 'go',
                    'alt' => 'Go!')));
            $this->assertEqual(
                    $form->submitImage(new SimpleByLabel('Go!'), 100, 101, array('a' => 'A')),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101, 'a' => 'A')));
        }
        
        function testButtonTag() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $widget = &new SimpleButtonTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go', 'id' => '9'));
            $widget->addContent('Go!');
            $form->addWidget($widget);
            $this->assertTrue($form->hasSubmit(new SimpleByName('go')));
            $this->assertTrue($form->hasSubmit(new SimpleByLabel('Go!')));
            $this->assertEqual(
                    $form->submitButton(new SimpleByName('go')),
                    new SimpleGetEncoding(array('go' => 'Go')));
            $this->assertEqual(
                    $form->submitButton(new SimpleByLabel('Go!')),
                    new SimpleGetEncoding(array('go' => 'Go')));
            $this->assertEqual(
                    $form->submitButton(new SimpleById(9)),
                    new SimpleGetEncoding(array('go' => 'Go')));
        }
        
        function testSingleSelectFieldSubmitted() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $select = &new SimpleSelectionTag(array('name' => 'a'));
            $select->addTag(new SimpleOptionTag(
                    array('value' => 'aaa', 'selected' => '')));
            $form->addWidget($select);
            $this->assertIdentical(
                    $form->submit(),
                    new SimpleGetEncoding(array('a' => 'aaa')));
        }
        
        function testSingleSelectFieldSubmittedWithPost() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array('method' => 'post')),
                    new SimpleUrl('htp://host'));
            $select = &new SimpleSelectionTag(array('name' => 'a'));
            $select->addTag(new SimpleOptionTag(
                    array('value' => 'aaa', 'selected' => '')));
            $form->addWidget($select);
            $this->assertIdentical(
                    $form->submit(),
                    new SimplePostEncoding(array('a' => 'aaa')));
        }
        
        function testUnchecked() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'type' => 'checkbox')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), false);
            $this->assertTrue($form->setField(new SimpleByName('me'), 'on'));
            $this->assertEqual($form->getValue(new SimpleByName('me')), 'on');
            $this->assertFalse($form->setField(new SimpleByName('me'), 'other'));
            $this->assertEqual($form->getValue(new SimpleByName('me')), 'on');
        }
        
        function testChecked() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'checkbox', 'checked' => '')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'a');
            $this->assertTrue($form->setField(new SimpleByName('me'), 'a'));
            $this->assertEqual($form->getValue(new SimpleByName('me')), 'a');
            $this->assertTrue($form->setField(new SimpleByName('me'), false));
            $this->assertEqual($form->getValue(new SimpleByName('me')), false);
        }
        
        function testSingleUncheckedRadioButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), false);
            $this->assertTrue($form->setField(new SimpleByName('me'), 'a'));
            $this->assertEqual($form->getValue(new SimpleByName('me')), 'a');
        }
        
        function testSingleCheckedRadioButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'a');
            $this->assertFalse($form->setField(new SimpleByName('me'), 'other'));
        }
        
        function testUncheckedRadioButtons() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), false);
            $this->assertTrue($form->setField(new SimpleByName('me'), 'a'));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'a');
            $this->assertTrue($form->setField(new SimpleByName('me'), 'b'));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'b');
            $this->assertFalse($form->setField(new SimpleByName('me'), 'c'));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'b');
        }
        
        function testCheckedRadioButtons() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'b');
            $this->assertTrue($form->setField(new SimpleByName('me'), 'a'));
            $this->assertIdentical($form->getValue(new SimpleByName('me')), 'a');
        }
        
        function testMultipleFieldsWithSameKey() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'a', 'type' => 'checkbox', 'value' => 'me')));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'a', 'type' => 'checkbox', 'value' => 'you')));
            $this->assertIdentical($form->getValue(new SimpleByName('a')), false);
            $this->assertTrue($form->setField(new SimpleByName('a'), 'me'));
            $this->assertIdentical($form->getValue(new SimpleByName('a')), 'me');
        }
    }
?>