<?php

use rsu\Models\Streets;
use rsu\Models\Houses;
use rsu\Models\Cities;
use rsu\Models\Regions;

class IndexController extends ControllerBase
{

    public function indexAction()
    {

    }

    /**
     * @search
     * @offset
     * @limit
     * @return mixed
     */
    public function getHousesAction()
    {
        $result = $jsonArr = [];
        $offset = $this->request->get('offset', null, 0);
        $limit = $this->request->get('limit', null, 10);
        $search = $this->request->get('search', null, '');
        echo "<pre/>";
        if($search != '') {
            $adapter = $this->getDI()->getShared('sphinx');


        } else {
            $houses = Houses::findAll($offset, $limit);
            foreach ($houses as $house) {
                $street = Streets::findById($house->street_id);
                $city = Cities::findById($house->city_id);
                $region = Regions::findById($city->region_id);
                $result = [
                    'id' => $house->id,
                    'region' =>$region->name,
                    'sity' => $city->name,
                    'street' => $street->name,
                    'number' => $house->number
                ];
                $jsonArr[] = $result;
            }
            return json_encode($jsonArr);
        }

        //        print_r($offset); exit;

//        if ($this->request->isAjax() == true) {
//
//        } else {
//            return $this->response->setStatusCode(404, 'Not Found');
//        }
//        do {
//            $houses = Houses::findAll($offset, $limit);
//            $offset += $limit;
//        } while ()



//        echo json_encode($jsonArr);

    }

    public function getHouseItemAction($house_id)
    {

    }

}

