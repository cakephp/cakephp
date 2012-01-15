<?php
App::uses('Set2', 'Utility');

class Set2Test extends CakeTestCase {

	public static function articleData() {
		return  array(
			array(
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
					),
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
					),
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
					),
					array(
						'id' => '2',
						'tag' => 'tag2',
					)
				),
				'Deep' => array(
					'Nesting' => array(
						'test' => array(
							1 => 'foo',
							2 => array(
								'and' => array('more' => 'stuff')
							)
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
				),
				'User' => array(
					'id' => '2',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
				),
				'User' => array(
					'id' => '3',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '4',
					'user_id' => '1',
					'title' => 'Fourth Article',
					'body' => 'Fourth Article Body',
				),
				'User' => array(
					'id' => '4',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '5',
					'user_id' => '1',
					'title' => 'Fifth Article',
					'body' => 'Fifth Article Body',
				),
				'User' => array(
					'id' => '5',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					),
				'Comment' => array(),
				'Tag' => array()
			)
		);
	}

/**
 * Test get()
 *
 * return void
 */
	public function testGet() {
		$data = self::articleData();

		$result = Set2::get(array(), '1.Article.title');
		$this->assertNull($result);

		$result = Set2::get($data, '');
		$this->assertNull($result);

		$result = Set2::get($data, '1.Article.title');
		$this->assertEquals('Second Article', $result);

		$result = Set2::get($data, '5.Article.title');
		$this->assertNull($result);

		$result = Set2::get($data, '1.Article.title.not_there');
		$this->assertNull($result);

		$result = Set2::get($data, '1.Article');
		$this->assertEquals($data[1]['Article'], $result);
	}

/**
 * Test dimensions.
 *
 * @return void
 */
	public function testDimensions() {
		$result = Set2::dimensions(array());
		$this->assertEquals($result, 0);

		$data = array('one', '2', 'three');
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => '1.1', '2', '3');
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => '3.1.1'));
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 2);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 2);

	}

/**
 * Test maxDimensions
 *
 * @return void
 */
	public function testMaxDimensions() {
		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 2);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 3);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => '2.1.1.1'))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 4);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 5);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 5);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 5);
	}

/**
 * Tests Set::flatten
 *
 * @return void
 */
	public function testFlatten() {
		$data = array('Larry', 'Curly', 'Moe');
		$result = Set2::flatten($data);
		$this->assertEquals($result, $data);

		$data[9] = 'Shemp';
		$result = Set2::flatten($data);
		$this->assertEquals($result, $data);

		$data = array(
			array(
				'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post'),
				'Author' => array('id' => '1', 'user' => 'nate', 'password' => 'foo'),
			),
			array(
				'Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body'),
				'Author' => array('id' => '3', 'user' => 'larry', 'password' => null),
			)
		);

		$result = Set2::flatten($data);
		$expected = array(
			'0.Post.id' => '1',
			'0.Post.author_id' => '1',
			'0.Post.title' => 'First Post',
			'0.Author.id' => '1',
			'0.Author.user' => 'nate',
			'0.Author.password' => 'foo',
			'1.Post.id' => '2',
			'1.Post.author_id' => '3',
			'1.Post.title' => 'Second Post',
			'1.Post.body' => 'Second Post Body',
			'1.Author.id' => '3',
			'1.Author.user' => 'larry',
			'1.Author.password' => null
		);
		$this->assertEquals($expected, $result);

		$data = array(
			array('Post' => array('id' => 1)),
			array('Post' => array('id' => 2)),
		);
		$result = Set2::flatten($data, '/');
		$expected = array(
			'0/Post/id' => '1',
			'1/Post/id' => '2',
		);
		$this->assertEquals($expected, $result);
	}

}
