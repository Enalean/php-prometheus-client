{ pkgs ? import (fetchTarball {
  url = "https://github.com/NixOS/nixpkgs/archive/98747f27ecfee70c8c97b195cbb94df80a074dda.tar.gz";
  sha256 = "04ss525ns5qqlggrdhvc6y4hqmshylda9yd0y99ddliyn15wmf27";
}) {} }:

let
  php = pkgs.php80.withExtensions ({ enabled, all }: with all; enabled ++ [ redis apcu ]);
in
pkgs.mkShell {
  nativeBuildInputs = [
    php.packages.composer
    php
    pkgs.docker-compose
  ];
}
