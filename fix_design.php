<?php
$files = glob('/www/wwwroot/backend/backend/resources/views/admin/analytics/*.blade.php');

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Replace SB Admin 2 card classes with Bootstrap 5 classes
    $content = preg_replace('/card stat-card border-left-[a-z]+ shadow h-100 py-2/', 'card stat-card h-100', $content);
    $content = preg_replace('/card stat-card border-left-[a-z]+ shadow mb-4 py-2/', 'card stat-card h-100 mb-4', $content);
    $content = str_replace('shadow-sm h-100', 'stat-card h-100', $content);
    
    // Replace font-awesome with bootstrap icons
    $content = str_replace('<i class="fas fa-arrow-up"></i>', '<i class="bi bi-arrow-up"></i>', $content);
    $content = str_replace('<i class="fas fa-arrow-down"></i>', '<i class="bi bi-arrow-down"></i>', $content);
    $content = str_replace('<i class="fas fa-dollar-sign', '<i class="bi bi-currency-dollar', $content);
    $content = str_replace('<i class="fas fa-shopping-cart', '<i class="bi bi-cart', $content);
    $content = str_replace('<i class="fas fa-chart-line', '<i class="bi bi-graph-up', $content);
    $content = str_replace('<i class="fas fa-users', '<i class="bi bi-people', $content);
    $content = str_replace('<i class="fas fa-chart-bar', '<i class="bi bi-bar-chart', $content);
    $content = str_replace('<i class="fas fa-boxes', '<i class="bi bi-box-seam', $content);
    $content = str_replace('<i class="fas fa-user-chart', '<i class="bi bi-person-badge', $content);
    $content = str_replace('<i class="fas fa-trophy', '<i class="bi bi-trophy', $content);
    $content = str_replace('<i class="fas fa-exclamation-triangle', '<i class="bi bi-exclamation-triangle', $content);
    $content = str_replace('<i class="fas fa-times-circle', '<i class="bi bi-x-circle', $content);
    $content = str_replace('<i class="fas fa-inbox', '<i class="bi bi-inbox', $content);
    $content = str_replace('<i class="fas fa-check-circle', '<i class="bi bi-check-circle', $content);
    $content = str_replace('<i class="fas fa-turtle', '<i class="bi bi-turtle', $content);
    
    // Update container
    $content = str_replace('<div class="container-fluid">', '', $content);
    $content = preg_replace('/<\/div>\s*@endsection/', '@endsection', $content); // remove closing div of container-fluid

    // Replace text-gray-800 to standard text-dark or text-primary
    $content = str_replace('text-gray-800', 'text-dark', $content);
    $content = str_replace('text-gray-300', 'text-muted', $content);
    
    // Convert stat card layout inside card-body
    // The pattern:
    // <div class="row no-gutters align-items-center">
    //     <div class="col mr-2">
    //         <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Revenue</div>
    //         <div class="h5 mb-0 font-weight-bold text-gray-800">৳...</div>
    //         <small ...>...</small>
    //     </div>
    //     <div class="col-auto">
    //         <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
    //     </div>
    // </div>
    // Will be rewritten somewhat, or at least replace text-xs to small etc.
    $content = str_replace('text-xs font-weight-bold', 'small fw-bold', $content);
    $content = str_replace('font-weight-bold', 'fw-bold', $content);
    $content = str_replace('no-gutters', 'g-0', $content);
    $content = str_replace('mr-2', 'me-2', $content);
    $content = str_replace('ml-2', 'ms-2', $content);
    $content = str_replace('pr-2', 'pe-2', $content);
    $content = str_replace('pl-2', 'ps-2', $content);
    $content = str_replace('text-right', 'text-end', $content);
    $content = str_replace('text-left', 'text-start', $content);
    $content = str_replace('float-right', 'float-end', $content);
    $content = str_replace('float-left', 'float-start', $content);

    file_put_contents($file, $content);
}
echo "Done replacing.";
