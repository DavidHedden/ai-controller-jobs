<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2020
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Begin;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$aimeos = \TestHelperJobs::getAimeos();
		$this->context = \TestHelperJobs::getContext();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Process\Begin\Standard( $this->context, $aimeos );

		\Aimeos\MShop::cache( true );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Subscription process start', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Process subscriptions initially', $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$item = $this->getSubscription();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( 'subscription', $managerStub );

		$managerStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$item] ) ) );

		$managerStub->expects( $this->once() )->method( 'save' );

		$this->object->run();
	}


	public function testRunException()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$this->context->getConfig()->set( 'controller/common/subscription/process/processor/cgroup/groupids', ['1'] );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( 'subscription', $managerStub );

		$managerStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$managerStub->create()] ) ) );

		$managerStub->expects( $this->once() )->method( 'save' )
			->will( $this->throwException( new \Exception() ) );

		$this->object->run();
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'subscription' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		if( ( $item = $manager->search( $search )->first() ) !== null ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}
}
