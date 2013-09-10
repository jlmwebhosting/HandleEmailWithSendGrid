<?php
$app->post('/endpoints/email', function () use ($app, $c) {  
    $req = $app->request();
    $log = $app->getLog();

    $log->info('Received email');

    $to = $req->post('to');
    $log->info('Email is to ' . $to);

    preg_match_all('/\b[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]+\b/i', $to, $result, PREG_PATTERN_ORDER);
    foreach ($result[0] as $toAddress) {
        // handle the from address
        $alias = substr($toAddress, 0, strpos($toAddress, '@'));    
        $user = $c['db']->users->where('alias = ?', $alias)->fetch();
        if ($user) {
            // great, we've got a valid user
            $numAttachments = $req->post('attachments');
            $uploadedFiles = array();

            if ($numAttachments) {
                foreach ($_FILES as $key => $file) {
                    $log->info('saving file to ' . $c['config']['path.uploads'] . $file['name']);
                    move_uploaded_file($file['tmp_name'], $c['config']['path.uploads'] . $file['name']);
                    $uploadedFiles[] = $file['name'];
                }
            }
            // ... so create a new post
            $c['db']->posts()->insert(array(
                'title'   => $req->post('subject'),
                'body'    => ($req->post('html')) ? $req->post('html') : $req->post('text'),          
                'user_id' => $user['id'],
                'image'   => (count($uploadedFiles)) ? $uploadedFiles[0] : ''
            ));
        }
    }
});
