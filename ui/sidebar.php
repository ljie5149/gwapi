<!-- Sidebar -->
<?php
    global $g_sidemenu, $g_ProjectIcon;
    global $g_funcidx_main, $g_funcidx_srch, $g_funcidx_add, $g_funcidx_edit, $g_funcidx_delete, $g_funcidx_import, $g_funcidx_export;

    echo '<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="./index.php">
                <img class="side-bar-home" src="'.$g_ProjectIcon.'" />
                <div class="sidebar-brand-text mx-3">'.$g_ProjectName.'</div>
            </a>';
    $root       = $g_sidemenu['root'     ];
    $root_id    = $g_sidemenu['root_id'  ];
    $root_href  = $g_sidemenu['root_href'];
    $root_icon  = $g_sidemenu['root_icon'];
    for ($i = 0; $i < count($root); $i++) {
        if (count($g_sidemenu[$root[$i]]) === 0) {
            echo '<li id="'.$root_id[$i].'" class="nav-item">
                    <a class="nav-link" href="'.$root_href[$i].'">
                        <i class="fas fa-fw '.$root_icon[$i].'"></i>
                        <span>'.$root[$i].'</span>
                    </a>
                  </li>';
            if ($i === 0) echo '<hr class="sidebar-divider">';
        } else {
            $hidden = '';// (getAuthorEnable($root_id[$i], $authority, $g_funcidx_main, true)) ? '' : 'hidden';
            echo '<li id="'.$root_id[$i].$root_id[$i].'" class="nav-item" '.$hidden.'>
                    <a id="'.$root_id[$i].'" class="nav-link collapsed" href="'.$root_href[$i].'" data-toggle="collapse" data-target="#collapseUtilities'.($i - 1).'" aria-expanded="true" aria-controls="collapseUtilities'.($i - 1).'">
                        <i class="fas fa-fw '.$root_icon[$i].'"></i>
				        <span>'.$root[$i].'</span>
			        </a>
                    <div id="collapseUtilities'.($i - 1).'" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
				        <div class="bg-white py-2 collapse-inner rounded">';
            
            if (count($g_sidemenu[$root[$i]]) > 0) {
                $item       = $g_sidemenu[$root[$i]         ];
                $item_id    = $g_sidemenu[$root[$i].'_id'   ];
                $item_href  = $g_sidemenu[$root[$i].'_href' ];
                for ($j = 0; $j < count($item); $j++) {
                    $sub_hidden = ''; //(getAuthorEnable($item_id[$j], $authority, $g_funcidx_srch)) ? '' : 'hidden';
                    echo    '<a class="collapse-item" id="'.$item_id[$j].'" href="'.$item_href[$j].'" '.$sub_hidden.'>'.$item[$j].'</a>';
                }
            }

            echo '      </div>
                    </div>
                  </li>';
        }
    }
    echo '<hr class="sidebar-divider d-none d-md-block">
          <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
          </div>
    </ul>';
?>