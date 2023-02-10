{ pkgs ? import (fetchTarball {
  url = "https://github.com/NixOS/nixpkgs/archive/747927516efcb5e31ba03b7ff32f61f6d47e7d87.tar.gz";
  sha256 = "1s4xabv59r99z8vd74w3r84kkxwqggqir3b0nh3ma04mni0m40gf";
}) {} }:

let
  php = pkgs.php81.withExtensions ({ enabled, all }: with all; enabled ++ [ redis apcu ]);
in
pkgs.mkShell {
  nativeBuildInputs = [
    php.packages.composer
    php
    pkgs.docker-compose
  ];
}
