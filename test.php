<?php
$req = Request::create('/api/admin/guests', 'GET');
$req->headers->set('Accept', 'application/json');
$req->setUserResolver(function() { return App\Models\User::first(); });
$response = app()->handle($req);
echo $response->getContent();
