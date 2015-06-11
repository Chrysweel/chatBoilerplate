<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Provider\Configuration;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('AppBundle:Default:index.html.twig');
    }

    public function logoAction()
    {

    	$parameters = Configuration::loadYml($this->getRequest());

    	$affiliate_name = "Project name";

    	if (array_key_exists("affiliate_name", $parameters)){
    		$affiliate_name =  $parameters['affiliate_name'];
    	}

        return $this->render('AppBundle:Default:logo.html.twig', array('affiliate_name' => $affiliate_name));
    }


    /**
     * @return \Ant\CoreBundle\Http\ApiRequestAllow
     */

    private function getApiRequestAllow()
    {
        return $this->container->get('ant_core.http.api_request_allow');
    }

    /**
     * Resolve api calls
     *
     * @param Request $request
     *
     * @return Response
     */
    public function apiAction(Request $request)
    {

        if ($request->isXmlHttpRequest() or $request->headers->get('Content-Type') =='application/json') {
            $pathInfo = $request->getPathInfo();

            if(!$this->getApiRequestAllow()->isAllow($pathInfo)){
                throw new NotFoundHttpException('No route allow for " '.$request->getMethod().' '.$pathInfo.'"');
            }

            $query_string = null;

            if ($request->getQueryString()){
                $query_string = '?' .$request->getQueryString();
            }
            $url_api = $request->getPathInfo() . $query_string;

            $apiUri = trim($url_api,'/');

            try{
                return $this->container->get('ant_core.http.api_client')->sendRequest('GET',$apiUri);
            }catch (ClientErrorResponseException $e){
                $headersGuzzle = $e->getResponse()->getHeaders()->toArray();
                $headersSymfony = array();
                foreach($headersGuzzle as $key=>$value){
                    //this header is not suporter in symfony
                    if($key != 'Transfer-Encoding' && $value !== 'chunked'){
                        $headersSymfony[$key] = $value[0];
                    }
                }
                return new Response($e->getResponse()->getBody(true),
                    $e->getResponse()->getStatusCode(),
                    $headersSymfony
                );
            }
        }else{
            throw new NotFoundHttpException();
        }
    }

}
