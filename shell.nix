{ pkgs ? import (fetchTarball {
  url = "https://github.com/NixOS/nixpkgs/archive/ebc94f855ef25347c314258c10393a92794e7ab9.tar.gz";
  sha256 = "sha256-UMVihg0OQ980YqmOAPz+zkuCEb9hpE5Xj2v+ZGNjQ+M=";
}) {} }:

let
  php = pkgs.php84.withExtensions ({ enabled, all }: with all; enabled ++ [ redis apcu ]);
in
pkgs.mkShell {
  nativeBuildInputs = [
    php.packages.composer
    php
    pkgs.docker-compose
  ];
}
