<?php namespace Robbo\Skeleton;

class Router implements \XenForo_Route_Interface {
    
    protected $routePath;
    protected $request;
    protected $router;
    
    public function match($routePath, \Zend_Controller_Request_Http $request, \XenForo_Router $router)
    {
        $this->routePath = $routePath;
        $this->request = $request;
        $this->router = $router;
        
        $method = trim($request->getParam('_origRoutePath'), '/').'Route';
        
        if (method_exists($this, $method))
        {
            return $this->$method();
        }
        
        return false;
    }
    
    protected function dragonRoute()
    {
        class_exists('Robbo\Skeleton\Controller\Dragon');
        return $this->router->getRouteMatch('Robbo\Skeleton\Controller\Dragon', $this->routePath);
    }
}
