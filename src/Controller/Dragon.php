<?php namespace Robbo\Skeleton\Controller;

class Dragon extends \XenForo_ControllerPublic_Abstract {
    
    public function actionIndex()
    {
        return $this->responseMessage('Dragon Rawwr');
    }
}