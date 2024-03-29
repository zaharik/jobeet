<?php

include(dirname(__FILE__).'/../../bootstrap/functional.php');
 
$browser = new JobeetTestFunctional(new sfBrowser());
$browser->loadData();
 
$browser->info('1 - The homepage')->
  get('/ru/')->
  with('request')->begin()->
    isParameter('module', 'job')->
    isParameter('action', 'index')->
  end()->
  with('response')->begin()->
    info('  1.1 - Expired jobs are not listed')->
    checkElement('.jobs td.position:contains("expired")', false)->
  end()
;
 
$max = sfConfig::get('app_max_jobs_on_homepage');
 
$browser->info('1 - The homepage')->
  info(sprintf('  1.2 - Only %s jobs are listed for a category', $max))->
  with('response')->
    checkElement('.category_programming tr', $max)
;
 
$browser->info('1 - The homepage')->
  get('/ru/')->
  info('  1.3 - A category has a link to the category page only if too many jobs')->
  with('response')->begin()->
    checkElement('.category_design .more_jobs', false)->
    checkElement('.category_programming .more_jobs')->
  end()
;
 
$browser->info('1 - The homepage')->
  info('  1.4 - Jobs are sorted by date')->
  with('response')->begin()->
    checkElement(sprintf('.category_programming tr:first a[href*="/%d/"]', $browser->getMostRecentProgrammingJob()->getId()))->
  end()
;
 
$job = $browser->getMostRecentProgrammingJob();
 
$browser->info('2 - The job page')->
  get('/ru/')->
 
  info('  2.1 - Each job on the homepage is clickable and give detailed information')->
  click('Web Developer', array(), array('position' => 1))->
  with('request')->begin()->
    isParameter('module', 'job')->
    isParameter('action', 'show')->
    isParameter('company_slug', $job->getCompanySlug())->
    isParameter('location_slug', $job->getLocationSlug())->
    isParameter('position_slug', $job->getPositionSlug())->
    isParameter('id', $job->getId())->
  end()->
 
  info('  2.2 - A non-existent job forwards the user to a 404')->
  get('/ru/job/foo-inc/milano-italy/0/painter')->
  with('response')->isStatusCode(404)->
 
  info('  2.3 - An expired job page forwards the user to a 404')->
  get(sprintf('/ru/job/sensio-labs/paris-france/%d/web-developer', $browser->getExpiredJob()->getId()))->
  with('response')->isStatusCode(404)
;

$browser->info('3 - Post a Job page')->
  info('  3.1 - Submit a Job')->
 
  get('/ru/job/new')->
  with('request')->begin()->
    isParameter('module', 'job')->
    isParameter('action', 'new')->
  end()->
 
  click('Preview your job', array('job' => array(
    'type'         => 'part-time',
    'company'      => 'Sensio Labs',
    'url'          => 'http://www.sensio.com/',
    'logo'         => sfConfig::get('sf_upload_dir').'/jobs/sensio-labs.gif',
    'position'     => 'Developer',
    'location'     => 'Atlanta, USA',
    'description'  => 'You will work with symfony to develop websites for our customers.',
    'how_to_apply' => 'Send me an email',
    'email'        => 'for.a.job@example.com',
    'is_public'    => false,
  )))->
 
  with('request')->begin()->
    isParameter('module', 'job')->
    isParameter('action', 'create')->
  end()->
  
  with('form')->begin()->
    hasErrors(FALSE)->
  end()->
       
  followRedirect()->

  with('request')->begin()->
    isParameter('module', 'job')->
    isParameter('action', 'show')->
  end()
;

$browser->setTester('doctrine', 'sfTesterDoctrine');

$browser->
  with('doctrine')->begin()->
    check('JobeetJob', array(
      'location'     => 'Atlanta, USA',
      'is_activated' => FALSE,
      'is_public'    => FALSE,
    ))->
  end()
;

$browser->
  info('  3.2 - Submit a Job with invalid values')->
 
  get('/ru/job/new')->
  click('Preview your job', array('job' => array(
    'company'      => 'Sensio Labs',
    'position'     => 'Developer',
    'location'     => 'Atlanta, USA',
    'email'        => 'not.an.email',
  )))->
 
  with('form')->begin()->
    hasErrors(4)->
    isError('type', 'required')->
    isError('description', 'required')->
    isError('how_to_apply', 'required')->
    isError('email', 'invalid')->
  end()
;

$browser->info('  3.3 - On the preview page, you can publish the job')->
  createJob(array('position' => 'FOO1'))->
  click('Publish', array(), array('method' => 'put', '_with_csrf' => true))->
 
  with('doctrine')->begin()->
    check('JobeetJob', array(
      'position'     => 'FOO1',
      'is_activated' => true,
    ))->
  end()
;

$browser->info('  3.4 - On the preview page, you can delete the job')->
  createJob(array('position' => 'FOO2'))->
  click('Delete', array(), array('method' => 'delete', '_with_csrf' => true))->
 
  with('doctrine')->begin()->
    check('JobeetJob', array(
      'position' => 'FOO2',
    ), false)->
  end()
;

$browser->info('  3.5 - When a job is published, it cannot be edited anymore')->
  createJob(array('position' => 'FOO3'), true)->
  get(sprintf('/ru/job/%s/edit', $browser->getJobByPosition('FOO3')->getToken()))->
 
  with('response')->begin()->
    //isStatusCode(404)->
  end()
;

$browser->info('  3.6 - A job validity cannot be extended before the job expires soon')->
  createJob(array('position' => 'FOO4'), true)->
  call(sprintf('/ru/job/%s/extend', $browser->getJobByPosition('FOO4')->getToken()), 'put', array('_with_csrf' => true))->
  with('response')->begin()->
    isStatusCode(404)->
  end()
;
 
$browser->info('  3.7 - A job validity can be extended when the job expires soon')->
  createJob(array('position' => 'FOO5'), true)
;
 
$job = $browser->getJobByPosition('FOO5');
$job->setExpiresAt(date('Y-m-d'));
$job->save();
 
$browser->
  call(sprintf('/ru/job/%s/extend', $job->getToken()), 'put', array('_with_csrf' => true))->
  with('response')->isRedirected()
;
 
$job->refresh();
$browser->test()->is(
  $job->getDateTimeObject('expires_at')->format('y/m/d'),
  date('y/m/d', time() + 86400 * sfConfig::get('app_active_days'))
);

$browser->
  info('4 - User job history')->
        
  loadData()->
  restart()->

  info('  4.1 - When the user access a job, it is added to its history')->
  get('/ru/')->
  click('Web Developer', array(), array('position' => 1))->
  get('/ru/')->
  with('user')->begin()->
    isAttribute('job_history', array($browser->getMostRecentProgrammingJob()->getId()))->
  end()->
 
  info('  4.2 - A job is not added twice in the history')->
  click('Web Developer', array(), array('position' => 1))->
  get('/ru/')->
  with('user')->begin()->
    isAttribute('job_history', array($browser->getMostRecentProgrammingJob()->getId()))->
  end()
;
  
$browser->setHttpHeader('X_REQUESTED_WITH', 'XMLHttpRequest');
$browser->
  info('5 - Live search')->
 
  get('/ru/search?query=sens*')->
  with('response')->begin()->
    checkElement('table tr', 2)->
  end()
;

$browser->setHttpHeader('ACCEPT_LANGUAGE', 'ru_UA,ru,en;q=0.7');
$browser->
  info('6 - User culture')->
 
  restart()->
 
  info('  6.1 - For the first request, symfony guesses the best culture')->
  get('/ru/')->
  //isRedirected()->
  followRedirect()->
  with('user')->isCulture('ru')->
 
  info('  6.2 - Available cultures are en and ru')->
  get('/it/')->
  with('response')->isStatusCode(404)
;
 
$browser->setHttpHeader('ACCEPT_LANGUAGE', 'en,ru;q=0.7');
$browser->
  info('  6.3 - The culture guessing is only for the first request')->
 
  get('/ru/')->
  //isRedirected()->
  followRedirect()->
  with('user')->isCulture('en')
;