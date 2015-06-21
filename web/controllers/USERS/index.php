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

$app->match('/USERS/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
        		'usr_id', 
		'usr_nom', 
		'usr_prenom', 
		'usr_adresse', 
		'usr_cp', 
		'usr_ville', 
		'usr_tel', 
		'usr_mail', 
		'usr_password', 

    );

    $table_columns_names = array(
        		'', 
		'', 
		'', 
		'', 
		'', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `USERS`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `USERS`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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

$app->match('/USERS', function () use ($app) {
    
	$table_columns = array(
		'usr_id', 
		'usr_nom', 
		'usr_prenom', 
		'usr_adresse', 
		'usr_cp', 
		'usr_ville', 
		'usr_tel', 
		'usr_mail', 
		'usr_password', 

    );

    $primary_key = "usr_id";	

    return $app['twig']->render('USERS/list.html.twig', array(
    	"table_columns" => $table_columns,
        "table_columns_names" => $table_columns_names,
        "primary_key" => $primary_key
    ));
        
})
->bind('USERS_list');



$app->match('/USERS/create', function () use ($app) {
    
    $initial_data = array(
		'usr_nom' => '', 
		'usr_prenom' => '', 
		'usr_adresse' => '', 
		'usr_cp' => '', 
		'usr_ville' => '', 
		'usr_tel' => '', 
		'usr_mail' => '', 
		'usr_password' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('usr_nom', 'text', array('required' => false));
	$form = $form->add('usr_prenom', 'text', array('required' => false));
	$form = $form->add('usr_adresse', 'text', array('required' => false));
	$form = $form->add('usr_cp', 'text', array('required' => false));
	$form = $form->add('usr_ville', 'text', array('required' => false));
	$form = $form->add('usr_tel', 'text', array('required' => false));
	$form = $form->add('usr_mail', 'text', array('required' => false));
	$form = $form->add('usr_password', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `USERS` (`usr_nom`, `usr_prenom`, `usr_adresse`, `usr_cp`, `usr_ville`, `usr_tel`, `usr_mail`, `usr_password`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['usr_nom'], $data['usr_prenom'], $data['usr_adresse'], $data['usr_cp'], $data['usr_ville'], $data['usr_tel'], $data['usr_mail'], $data['usr_password']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'USERS created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('USERS_list'));

        }
    }

    return $app['twig']->render('USERS/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('USERS_create');



$app->match('/USERS/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `USERS` WHERE `usr_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('USERS_list'));
    }

    
    $initial_data = array(
		'usr_nom' => $row_sql['usr_nom'], 
		'usr_prenom' => $row_sql['usr_prenom'], 
		'usr_adresse' => $row_sql['usr_adresse'], 
		'usr_cp' => $row_sql['usr_cp'], 
		'usr_ville' => $row_sql['usr_ville'], 
		'usr_tel' => $row_sql['usr_tel'], 
		'usr_mail' => $row_sql['usr_mail'], 
		'usr_password' => $row_sql['usr_password'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('usr_nom', 'text', array('required' => false));
	$form = $form->add('usr_prenom', 'text', array('required' => false));
	$form = $form->add('usr_adresse', 'text', array('required' => false));
	$form = $form->add('usr_cp', 'text', array('required' => false));
	$form = $form->add('usr_ville', 'text', array('required' => false));
	$form = $form->add('usr_tel', 'text', array('required' => false));
	$form = $form->add('usr_mail', 'text', array('required' => false));
	$form = $form->add('usr_password', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `USERS` SET `usr_nom` = ?, `usr_prenom` = ?, `usr_adresse` = ?, `usr_cp` = ?, `usr_ville` = ?, `usr_tel` = ?, `usr_mail` = ?, `usr_password` = ? WHERE `usr_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['usr_nom'], $data['usr_prenom'], $data['usr_adresse'], $data['usr_cp'], $data['usr_ville'], $data['usr_tel'], $data['usr_mail'], $data['usr_password'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'USERS edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('USERS_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('USERS/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('USERS_edit');



$app->match('/USERS/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `USERS` WHERE `usr_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `USERS` WHERE `usr_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'USERS deleted!',
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

    return $app->redirect($app['url_generator']->generate('USERS_list'));

})
->bind('USERS_delete');






