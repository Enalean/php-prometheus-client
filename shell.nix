{ pkgs ? import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/84aa23742f6c72501f9cc209f29c438766f5352d.tar.gz") {} }:

let
  php = pkgs.php74.withExtensions ({ enabled, all }: with all; enabled ++ [ json redis apcu ]);
in
pkgs.mkShell {
  nativeBuildInputs = [
    php.packages.composer
    php
    pkgs.docker-compose
  ];
}
