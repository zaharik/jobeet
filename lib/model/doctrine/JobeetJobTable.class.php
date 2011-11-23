<?php

class JobeetJobTable extends Doctrine_Table
{
  /**
   * Returns an instance of this class.
   *
   * @return object JobeetJobTable
   */
  
  static public $types = array(
    'full-time' => 'Full time',
    'part-time' => 'Part time',
    'freelance' => 'Freelance',
  );
  
  public function getTypes(){
    return self::$types;
  }

  public static function getInstance()
  {
      return Doctrine_Core::getTable('JobeetJob');
  }

  public function retrieveActiveJob(Doctrine_Query $q)
  {
    return $this->addActiveJobsQuery($q)->fetchOne();
  }
 
  public function getActiveJobs(Doctrine_Query $q = null)
  {
    return $this->addActiveJobsQuery($q)->execute();
  }
  
  public function countActiveJobs(Doctrine_Query $q = null)
  {
    return $this->addActiveJobsQuery($q)->count();
  }
 
  public function addActiveJobsQuery(Doctrine_Query $q = null)
  {
    if (is_null($q))
    {
      $q = Doctrine_Query::create()
        ->from('JobeetJob j');
    }
 
    $alias = $q->getRootAlias();
 
    $q->andWhere($alias . '.expires_at > ?', date('Y-m-d h:i:s', time()))
      ->addOrderBy($alias . '.expires_at DESC');
    
    $q->andWhere($alias . '.is_activated = ?', 1);
 
    return $q;
  }
  
  public function cleanup($days)
  {
    $q = $this->createQuery('a')
      ->delete()
      ->andWhere('a.is_activated = ?', 0)
      ->andWhere('a.expires_at < ?', date('Y-m-d', time() - 86400 * $days));

    return $q->execute();
  }
}