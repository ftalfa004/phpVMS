<?php
/**
 * phpVMS - Virtual Airline Administration Software
 * Copyright (c) 2008 Nabeel Shahzad
 * For more information, visit www.phpvms.net
 *	Forums: http://www.phpvms.net/forum
 *	Documentation: http://www.phpvms.net/docs
 *
 * phpVMS is licenced under the following license:
 *   Creative Commons Attribution Non-commercial Share Alike (by-nc-sa)
 *   View license.txt in the root, or visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * @author Nabeel Shahzad
 * @copyright Copyright (c) 2008, Nabeel Shahzad
 * @link http://www.phpvms.net
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 */
 
class Schedules extends CodonModule
{
	
	public $gMap;
	
	public function __construct()
	{
		parent::__construct();
		$this->gMap = new GoogleMapAPI('routemap', 'phpVMS');
		$this->gMap->setAPIKey(GOOGLE_KEY);
	}
	
	public function HTMLHead()
	{
		if($this->get->page == 'detail' || $this->get->page == 'details')
		{
			$this->gMap->printHeaderJS();
    		$this->gMap->printMapJS();			
		}
	}
	
	public function index()
	{
		$this->view();
	}
	
	public function view()
	{
		if(isset($this->post->action) && $this->post->action == 'findflight')
		{
			$this->FindFlight();
			return;
		}
		
		$this->showSchedules();
	}
	
	public function detail($routeid='')
	{
		$this->details($routeid);
	}
	
	public function details($routeid = '')
	{
		//$routeid = $this->get->id;
		
		if(!is_numeric($routeid))
		{
			preg_match('/^([A-Za-z]{3})(\d*)/', $routeid, $matches);
			$code = $matches[1];
			$flightnum = $matches[2];
			
			$params = array('s.code'=>$code, 's.flightnum'=>$flightnum);
		}
		else
		{
			$params = array('s.id' => $routeid);
		}
		
		
		$scheddata = SchedulesData::findSchedules($params);
		$this->set('schedule', $scheddata[0]);
		
		$this->render('schedule_details.tpl');
		$this->render('route_map.tpl');
	}
	
	public function brief($routeid = '')
	{	
		if($routeid == '')
		{
			$this->set('message', 'You must be logged in to access this feature!');
			$this->render('core_error.tpl');
			return;
		}
		
		$schedules = SchedulesData::findSchedules(array('s.id' => $routeid));
		
		$this->set('schedule', $schedules[0]);
		$this->render('schedule_briefing.tpl');
	}
	
	public function boardingpass($routeid)
	{
		if($routeid == '')
		{
			$this->set('message', 'You must be logged in to access this feature!');
			$this->render('core_error.tpl');
			return;
		}
		
		$schedules = SchedulesData::findSchedules(array('s.id' => $routeid));
				
		$this->set('schedule', $schedules[0]);
		$this->render('schedule_boarding_pass.tpl');
	}
	
	public function bids()
	{
		if(!Auth::LoggedIn()) return;
			
		$this->set('bids', SchedulesData::GetBids(Auth::$userinfo->pilotid));
		$this->render('schedule_bids.tpl');
	}
	
	public function addbid()
	{
		if(!Auth::LoggedIn()) return;
				
		$routeid = $this->post->id;
		
		if($routeid == '')
		{
			echo 'No route passed';
			return;
		}
		
		// See if this is a valid route
		$route = SchedulesData::findSchedules(array('s.id' => $routeid));
		
		if(!is_array($route) && !isset($route[0]))
		{
			echo 'Invalid Route';
			return;
		}
		
		CodonEvent::Dispatch('bid_preadd', 'Schedules', $routeid);
		
		/* Block any other bids if they've already made a bid
		 */
		if(Config::Get('DISABLE_BIDS_ON_BID') == true)
		{
			$bids = SchedulesData::getBids(Auth::$userinfo->pilotid);
			
			# They've got somethin goin on
			if(count($bids) > 0)
			{
				echo 'Bid exists!';
				return;
			}
		}
		
		$ret = SchedulesData::AddBid(Auth::$userinfo->pilotid, $routeid);
		CodonEvent::Dispatch('bid_added', 'Schedules', $routeid);
		
		if($ret == true)
		{
			echo 'Bid added';
		}
		else
		{
			echo 'Already in bids!';
		}
	}
	
	public function removebid()
	{
		if(!Auth::LoggedIn()) return;
				
		SchedulesData::RemoveBid($this->post->id);
	}

	public function showSchedules()
	{
		$depapts = OperationsData::GetAllAirports();
		$equip = OperationsData::GetAllAircraftSearchList(true);
		
		$this->set('depairports', $depapts);
		$this->set('equipment', $equip);
		
		$this->render('schedule_searchform.tpl');
		
		# Show the routes. Remote this to not show them.
		$this->set('allroutes', SchedulesData::GetSchedules());
		
		$this->render('schedule_list.tpl');
	}
	
	public function findFlight()
	{
		
		if($this->post->depicao != '')
		{
			$params = array('s.depicao' => $this->post->depicao);
		}
		
		if($this->post->arricao != '')
		{
			$params = array('s.arricao' => $this->post->arricao);
		}
		
		if($this->post->equipment != '')
		{
			$params = array('a.name' => $this->post->equipment);
		}
		
		if($this->post->distance != '')
		{
			if($this->post->type == 'greater')
				$value = '> ';
			else
				$value = '< ';
			
			$value .= $this->post->distance;
			
			$params = array('s.distance' => $value);
		}
		
		$params['s.enabled'] = 1;
		$this->set('allroutes', SchedulesData::findSchedules($params));
		$this->render('schedule_results.tpl');
	}
	
	public function statsdaysdata($routeid)
	{
		$routeinfo = SchedulesData::findSchedules(array('s.id'=>$routeid));
		$routeinfo = $routeinfo[0];
		
		// Last 30 days stats
		$data = PIREPData::getIntervalDataByDays(array(
			'p.code' => $routeinfo->code, 
			'p.flightnum' => $routeinfo->flightnum,
		), 30);
		
		$this->create_line_graph('Schedule Flown Counts', $data);
	}
	
	protected function create_line_graph($title, $data)
	{	
		if(!$data)
		{
			$data = array();
		}
		
		$bar_values = array();
		$bar_titles = array();
		foreach($data as $val)
		{
			$bar_titles[] = $val->ym;
			$bar_values[] = floatval($val->total);
		}
		
		include CORE_LIB_PATH.'/php-ofc-library/open-flash-chart.php';

		$title = new title($title);

		// ------- LINE 2 -----
		$d = new solid_dot();
		$d->size(3)->halo_size(1)->colour('#3D5C56');

		$line = new line();
		$line->set_default_dot_style($d);
		$line->set_values( $bar_values );
		$line->set_width( 2 );
		$line->set_colour( '#3D5C56' );
		
		$x_labels = new x_axis_labels();
		$x_labels->set_labels( $bar_titles );

		$x = new x_axis();
		$x->set_labels( $x_labels );
		
		$chart = new open_flash_chart();
		$chart->set_title( $title );
		$chart->add_element( $line );
		$chart->set_y_axis( $y );
		$chart->set_x_axis( $x );
		$chart->set_bg_colour( '#FFFFFF' );

		echo $chart->toPrettyString();
	}
}