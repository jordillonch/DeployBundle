<?php


namespace JordiLlonch\Bundle\DeployBundle\Service;


use FSLock\FSLock;

class LockFS implements LockInterface
{
    protected $locks = array();

    public function acquire($id)
    {
        $isNewLock = false;
        if(!isset($this->locks[$id])) {
            $this->locks[$id] = new FSLock($id);
            $isNewLock = true;
        }
        $lockAdquired = $this->locks[$id]->acquire();

        if(!$lockAdquired && $isNewLock) unset($this->locks[$id]);

        return $lockAdquired;
    }

    public function release($id)
    {
        return $this->locks[$id]->release();
    }

    public function releaseAll()
    {
        $releaseResult = true;
        foreach ($this->locks as $id => $lock) {
            $releaseResult = $releaseResult * $this->release($id);
        }

        return $releaseResult;
    }


} 