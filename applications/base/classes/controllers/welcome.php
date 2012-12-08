<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

class Welcome extends SZ_Breeder
{
	
	function index()
	{
		$this->view->assign(array('message' => 'このページはデフォルトエンジンでレンダリングされました。'));
		//return Seezoo::$Response->redirect('welcome/sub');
		//$this->view->render('welcome_message');
		
	}
	
	function sub()
	{
		$this->view->swap('welcome/index');
		return array('message' => 'このページはデフォルトエンジンでレンダリングされました。');
	}
	
	function smarty()
	{
		$this->view->engine('smarty');
		$this->view->assign(array('message' => 'このページはSmartyでレンダリングされました。'));
		$this->view->render('welcome_message');
	}
	
	function phptal()
	{
		$this->view->engine('phptal');
		$this->view->assign(array('message' => 'このページはPHPTALでレンダリングされました。'));
		$this->view->render('welcome_message');
	}
	
	function twig()
	{
		$this->view->engine('twig');
		$this->view->assign(array('message' => 'このページはTwigでレンダリングされました。'));
		$this->view->render('welcome_message');
	}
}
