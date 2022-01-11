<?php


class Images
{
    public function getImagesSize($data = []): array
    {
        $imagesSize = [];
        foreach ($data as $item){
            if (file_exists($item)){
                $imagesSize[] = filesize($item);
            }else{
                $imagesSize[] = '';
            }
        }
        return $imagesSize;
    }
}
echo "<pre>";
$data = [
    '../images/img.png',
    '../images/img_1.png'
];
var_dump((new Images())->getImagesSize($data));