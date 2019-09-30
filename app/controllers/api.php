<?php
namespace ReleaseInsights;

switch ($request->getService()) {
    case 'entity':
        $repo = $request->parameters[2];
        $entity = $request->extra_parameters['id'];
        $json = include MODELS . 'api/entity.php';
        if (empty($json)) {
            $request->error = 'Entity not available';
            $json = $request->invalidAPICall();
        }
        break;
    default:
        return false;
}

include VIEWS . 'json.php';
