<?php

class IndexController extends SZ_Breeder
{
	public function index()
	{
		$db = Database::grow();
		$times = $db->query('SELECT times FROM perooon LIMIT 1');
		return array('count' => $times[0]);
	}
	
	public function perooon()
	{
		$oauth = new SZ_Oauth('twitter');
		$oauth->configure(array(
			'consumer_key' => '',
			'consumer_secret' => ''
		));
		
		if ( $oauth->isAuthorized() )
		{
			if ( FALSE !== $oauth->tweet('ぺろーん(๑╹ڡ╹๑)')
			     || FALSE !== $oauth->tweet('ぺろーん(๑╹ڡ╹๑)  ') )
			{
                PerooonModel::updateCount();
				$msg = 'ぺろーんしました。';
			}
			else
			{
				if ( preg_match('/duplicate/u', $oauth->getError()) )
				{
					$msg = 'すでにぺろーんずみでした。';
				}
				else
				{
					$msg = 'ぺろーんできませんでした。';
				}
			}
			$this->view->swap('index/success');
			return array('message' => $msg);
		}
		if ( ! $oauth->auth() )
		{
			echo $oauth->getError();
		}
		return TRUE;
	}
	
	public function perooon_callback()
	{
		$oauth = new SZ_Oauth('twitter');
		$oauth->configure(array(
			'consumer_key' => '',
			'consumer_secret' => ''
		));
		if ( $oauth->auth() )
		{
			if ( FALSE !== $oauth->tweet('ぺろーん(๑╹ڡ╹๑)')
			     || FALSE !== $oauth->tweet('ぺろーん(๑╹ڡ╹๑)  ') )
			{
                PerooonModel::updateCount();
				$msg = 'ぺろーんしました。';
			}
			else
			{
				if ( preg_match('/duplicate/u', $oauth->getError()) )
				{
					$msg = 'すでにぺろーんずみでした。';
				}
				else
				{
					$msg = 'ぺろーんできませんでした。';
				}
			}
			$this->view->swap('index/success');
			return array('message' => $msg);
		}
		exit('なんかおかしい(ू˃̣̣̣̣̣̣︿˂̣̣̣̣̣̣ ू)');
	}
}
