<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/PLATS_TYPES/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
        		'plt_id', 
		'plt_libelle', 
		'plt_image_path', 
		'plt_description', 

    );

    $table_columns_names = array(
        		'', 
		'', 
		'', 
		'', 

    );


    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `PLATS_TYPES`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `PLATS_TYPES`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});

$app->match('/PLATS_TYPES', function () use ($app) {
    
	$table_columns = array(
		'plt_id', 
		'plt_libelle', 
		'plt_image_path', 
		'plt_description', 

    );

    $primary_key = "plt_id";	

    return $app['twig']->render('PLATS_TYPES/list.html.twig', array(
    	"table_columns" => $table_columns,
        "table_columns_names" => $table_columns_names,
        "primary_key" => $primary_key
    ));
        
})
->bind('PLATS_TYPES_list');



$app->match('/PLATS_TYPES/create', function () use ($app) {
    
    $initial_data = array(
		'plt_libelle' => '', 
		'plt_image_path' => '', 
		'plt_description' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('plt_libelle', 'text', array('required' => true));
	$form = $form->add('plt_image_path', 'text', array('required' => false));
	$form = $form->add('plt_description', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `PLATS_TYPES` (`plt_libelle`, `plt_image_path`, `plt_description`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['plt_libelle'], $data['plt_image_path'], $data['plt_description']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'PLATS_TYPES created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('PLATS_TYPES_list'));

        }
    }

    return $app['twig']->render('PLATS_TYPES/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('PLATS_TYPES_create');



$app->match('/PLATS_TYPES/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `PLATS_TYPES` WHERE `plt_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('PLATS_TYPES_list'));
    }

    
    $initial_data = array(
		'plt_libelle' => $row_sql['plt_libelle'], 
		'plt_image_path' => $row_sql['plt_image_path'], 
		'plt_description' => $row_sql['plt_description'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('plt_libelle', 'text', array('required' => true));
	$form = $form->add('plt_image_path', 'text', array('required' => false));
	$form = $form->add('plt_description', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `PLATS_TYPES` SET `plt_libelle` = ?, `plt_image_path` = ?, `plt_description` = ? WHERE `plt_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['plt_libelle'], $data['plt_image_path'], $data['plt_description'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'PLATS_TYPES edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('PLATS_TYPES_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('PLATS_TYPES/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('PLATS_TYPES_edit');



$app->match('/PLATS_TYPES/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `PLATS_TYPES` WHERE `plt_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `PLATS_TYPES` WHERE `plt_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'PLATS_TYPES deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('PLATS_TYPES_list'));

})
->bind('PLATS_TYPES_delete');






