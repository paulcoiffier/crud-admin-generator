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

$app->match('/PLATS/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
        		'pla_id', 
		'pla_libelle', 
		'pla_description', 
		'pla_prix', 
		'PLATS_TYPES_ID', 
		'pla_image_path', 

    );

    $table_columns_names = array(
        		'ID', 
		'Libelle', 
		'Description', 
		'Prix', 
		'Type de plat', 
		'Image', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `PLATS`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `PLATS`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'PLATS_TYPES_ID'){
			    $findexternal_sql = 'SELECT `plt_name` FROM `PLATS_TYPES` WHERE `plt_id` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['plt_name'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
			}


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});

$app->match('/PLATS', function () use ($app) {
    
	$table_columns = array(
		'pla_id', 
		'pla_libelle', 
		'pla_description', 
		'pla_prix', 
		'PLATS_TYPES_ID', 
		'pla_image_path', 

    );

    $primary_key = "pla_id";	

    return $app['twig']->render('PLATS/list.html.twig', array(
    	"table_columns" => $table_columns,
        "table_columns_names" => $table_columns_names,
        "primary_key" => $primary_key
    ));
        
})
->bind('PLATS_list');



$app->match('/PLATS/create', function () use ($app) {
    
    $initial_data = array(
		'pla_libelle' => '', 
		'pla_description' => '', 
		'pla_prix' => '', 
		'PLATS_TYPES_ID' => '', 
		'pla_image_path' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `plt_id`, `plt_name` FROM `PLATS_TYPES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['plt_id']] = $findexternal_row['plt_name'];
	}
	if(count($options) > 0){
	    $form = $form->add('PLATS_TYPES_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('PLATS_TYPES_ID', 'text', array('required' => true));
	}



	$form = $form->add('pla_libelle', 'text', array('required' => true));
	$form = $form->add('pla_description', 'text', array('required' => true));
	$form = $form->add('pla_prix', 'text', array('required' => true));
	$form = $form->add('pla_image_path', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `PLATS` (`pla_libelle`, `pla_description`, `pla_prix`, `PLATS_TYPES_ID`, `pla_image_path`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['pla_libelle'], $data['pla_description'], $data['pla_prix'], $data['PLATS_TYPES_ID'], $data['pla_image_path']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'PLATS created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('PLATS_list'));

        }
    }

    return $app['twig']->render('PLATS/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('PLATS_create');



$app->match('/PLATS/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `PLATS` WHERE `pla_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('PLATS_list'));
    }

    
    $initial_data = array(
		'pla_libelle' => $row_sql['pla_libelle'], 
		'pla_description' => $row_sql['pla_description'], 
		'pla_prix' => $row_sql['pla_prix'], 
		'PLATS_TYPES_ID' => $row_sql['PLATS_TYPES_ID'], 
		'pla_image_path' => $row_sql['pla_image_path'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `plt_id`, `plt_name` FROM `PLATS_TYPES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['plt_id']] = $findexternal_row['plt_name'];
	}
	if(count($options) > 0){
	    $form = $form->add('PLATS_TYPES_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('PLATS_TYPES_ID', 'text', array('required' => true));
	}


	$form = $form->add('pla_libelle', 'text', array('required' => true));
	$form = $form->add('pla_description', 'text', array('required' => true));
	$form = $form->add('pla_prix', 'text', array('required' => true));
	$form = $form->add('pla_image_path', 'text', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `PLATS` SET `pla_libelle` = ?, `pla_description` = ?, `pla_prix` = ?, `PLATS_TYPES_ID` = ?, `pla_image_path` = ? WHERE `pla_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['pla_libelle'], $data['pla_description'], $data['pla_prix'], $data['PLATS_TYPES_ID'], $data['pla_image_path'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'PLATS edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('PLATS_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('PLATS/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('PLATS_edit');



$app->match('/PLATS/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `PLATS` WHERE `pla_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `PLATS` WHERE `pla_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'PLATS deleted!',
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

    return $app->redirect($app['url_generator']->generate('PLATS_list'));

})
->bind('PLATS_delete');






