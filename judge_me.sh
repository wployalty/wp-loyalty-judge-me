echo "WPLoyalty Custom Referral pack"
current_dir="$PWD/"
pack_folder="wp-loyalty-judge-me"
plugin_pack_folder="wployalty_judge_me"
folder_sperate="/"
pack_compress_folder=$current_dir$pack_folder
composer_run() {
  # shellcheck disable=SC2164
  cd "$plugin_pack_folder"
  composer install --no-dev -q
  composer update --no-dev -q
  echo "done"
  cd $current_dir
}
copy_folder() {
  cd $current_dir
  echo "Compress Dir $pack_compress_folder"
  from_folder="wployalty_judge_me"
  from_folder_dir=$current_dir$from_folder
  move_dir=("App" "Assets" "i18n" "vendor" "wp-loyalty-judge-me.php")
  if [ -d "$pack_compress_folder" ]; then
    rm -r "$pack_folder"
    mkdir "$pack_folder"
    # shellcheck disable=SC2068
    for dir in ${move_dir[@]}; do
      cp -r "$from_folder_dir/$dir" "$pack_compress_folder/$dir"
    done
  else
    mkdir "$pack_folder"
    # shellcheck disable=SC2068
    for dir in ${move_dir[@]}; do
      cp -r "$from_folder_dir/$dir" "$pack_compress_folder/$dir"
    done
  fi
}
zip_folder() {
  rm "$pack_folder".zip
  zip -r "$pack_folder".zip $pack_folder -q
  zip -d "$pack_folder".zip __MACOSX/\*
  zip -d "$pack_folder".zip \*/.DS_Store
}
echo "Composer Run:"
composer_run
echo "Copy Folder:"
copy_folder
echo "Zip Folder:"
zip_folder

echo "End"
