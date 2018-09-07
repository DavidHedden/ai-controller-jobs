<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2017
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;


	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->endpoint = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
			2 => 'product.lists.type',
			3 => 'attribute.type',
			4 => 'attribute.code',
			5 => 'product.lists.type',
			6 => 'attribute.type',
			7 => 'attribute.code',
			8 => 'product.lists.type',
		);

		$data = array(
			0 => 'length',
			1 => '30',
			2 => 'variant',
			3 => 'width',
			4 => '29',
			5 => 'variant',
			6 => 'color',
			7 => 'white',
			8 => 'variant',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$pos = 0;
		$listItems = $product->getListItems();
		$expected = array(
			array( 'variant', 'length', '30' ),
			array( 'variant', 'width', '29' ),
			array( 'variant', 'color', 'white' ),
		);

		$this->assertEquals( 3, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'attribute', $listItem->getDomain() );
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getCode() );
			$pos++;
		}
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
			2 => 'product.lists.type',
		);

		$data = array(
			0 => 'color',
			1 => "white\nblack\naimeos",
			2 => 'variant',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$pos = 0;
		$codes = array( 'white', 'black', 'aimeos' );

		foreach( $product->getListItems() as $listItems )
		{
			$this->assertEquals( 3, count( $listItems ) );

			foreach( $listItems as $listItem )
			{
				$this->assertEquals( 1, $listItem->getStatus() );
				$this->assertEquals( 'attribute', $listItem->getDomain() );
				$this->assertEquals( 'variant', $listItem->getType() );
				$this->assertEquals( $codes[$pos], $listItem->getRefItem()->getCode() );
				$pos++;
			}
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
		);

		$data = array(
			0 => 'length',
			1 => '30',
		);

		$dataUpdate = array(
			0 => 'width',
			1 => '29',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );
		$object->process( $product, $dataUpdate );


		$listItems = $product->getListItems();
		$listItem = reset( $listItems );

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'width', $listItem->getRefItem()->getType() );
		$this->assertEquals( '29', $listItem->getRefItem()->getCode() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
		);

		$data = array(
			0 => 'length',
			1 => '30',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, [], $this->endpoint );
		$object->process( $product, [] );


		$listItems = $product->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
			2 => 'attribute.type',
			3 => 'attribute.code',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'length',
			3 => '30',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessInvalid()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
		);

		$data = array(
			0 => 'invalid',
			1 => '30',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, $mapping, $this->endpoint );

		try {
			$object->process( $product, $data );
		} catch( \Exception $e ) {
			return;
		}

		$this->markTestFail( 'No exception thrown' );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'attribute.type',
			1 => 'attribute.code',
			2 => 'product.lists.type',
			3 => 'attribute.type',
			4 => 'attribute.code',
			5 => 'product.lists.type',
		);

		$data = array(
			0 => 'length',
			1 => '32',
			2 => 'custom',
			3 => 'width',
			4 => '30',
			5 => 'default',
		);

		$this->context->getConfig()->set( 'controller/common/product/import/csv/processor/attribute/listtypes', array( 'default' ) );

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Attribute\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();
		$listItem = reset( $listItems );

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'default', $listItem->getType() );
		$this->assertEquals( 'width', $listItem->getRefItem()->getType() );
		$this->assertEquals( '30', $listItem->getRefItem()->getCode() );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::createManager( $this->context );
		return $manager->createItem()->setCode( $code );
	}
}