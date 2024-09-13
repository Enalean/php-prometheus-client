{ pkgs ? import (fetchTarball {
  url = "https://github.com/NixOS/nixpkgs/archive/280db3decab4cbeb22a4599bd472229ab74d25e1.tar.gz";
  sha256 = "17n9wji64l7d16s8r100ypwlxkmwrypll4q3wkkfjswbilxkqjr6";
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
