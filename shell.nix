{ pkgs ? import (fetchTarball {
  url = "https://github.com/NixOS/nixpkgs/archive/6d97d419e5a9b36e6293887a89a078cf85f5a61b.tar.gz";
  sha256 = "10y6ply5jhg9pwq13zldmipxh8dscmawx5syi1i6rb773xnnr452";
}) {} }:

let
  php = pkgs.php82.withExtensions ({ enabled, all }: with all; enabled ++ [ redis apcu ]);
in
pkgs.mkShell {
  nativeBuildInputs = [
    php.packages.composer
    php
    pkgs.docker-compose
  ];
}
