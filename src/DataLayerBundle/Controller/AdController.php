<?php

namespace DataLayerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdController extends Controller
{
    const VEADS_BASE_URL = 'https://veads.veinteractive.com/adxml.php?a=%s';

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////  GET
    /**
     * Returns ads from external url
     *
     * @param $slug
     * @return mixed
     */
    public function getAdsAction(Request $request, $slug)
    {
        $session = $request->getSession();

        //compute url
        $url = sprintf(self::VEADS_BASE_URL, $slug);

        $this->get('logger')->info($url);

        //download ads xml
        $contents = file_get_contents($url);

        //serialize and convert to json and parse CDATA
        $xml = FALSE;
        try{
            $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA);
        }catch(Exception $e)
        {}

        if ( $xml !== FALSE)
        {
            $json = json_encode($xml);
            $json = json_decode($json);

//            $session->remove('trackedId');
            //filter by clickTracking
            if ( $session->has('trackedId'))
            {

                //Here id is an index, that's why I use splice.
                //We should have an id per ad.
                $adId = $session->get('trackedId');
                $this->get('logger')->info('OOOOOO:'.$adId);
                $json->scenes->scene = array_splice($json->scenes->scene , $adId , 1);
            }
            ////////////////////////

            return new Response(json_encode($json) , 200);

        }else{
            $this->get('logger')->error('The xml is not well-formatted');

            $view = View::create()
                ->setStatusCode(400)
                ->setData('The xml is not well-formatted');
            return $this->get('fos_rest.view_handler')->handle($view);
        }
    }
}