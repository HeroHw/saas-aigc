<?php

namespace Plugin\Alen\Dto;

class InstallScript {

    public function __invoke(){
        echo "Commands to be executed when uninstalling the plug-in";
    }

}