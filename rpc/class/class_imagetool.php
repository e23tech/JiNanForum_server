<?php

class imagetool {

    var $type; //图片类型
	var $mime;
    var $width; //实际宽度
    var $height; //实际高度
    var $resize_width;   //改变后的宽度
    var $resize_height; //改变后的高度
    var $srcimg; //源图象
    var $dstimg;   //目标图象地址
    var $im;  //临时创建的图象

    function imagetool() {}
	
	//生成缩略图
    function thumb($source, $target, $width, $height, $cut=0, $quality=75) {
		$this->srcimg = $source;
		$this ->dst_img($target); //目标图象地址
        $this->resize_width = $width;
        $this->resize_height = $height;
        if(!$this->init()) {
			return;
		}

		//如果图片尺寸小于缩略图尺寸则返回
		if( $this->resize_width > $this->width || $this->resize_height > $this->height) {
			if(copy($source, $target)) {
				return true;
			}
		}
        
        $resize_ratio = ($this->resize_width)/($this->resize_height); //改变后的图象的比例
        $ratio = ($this->width)/($this->height); //实际图象的比例
		
        if($cut) { //裁图
            if($ratio>=$resize_ratio) { //高度优先
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width,$this->resize_height, (($this->height)*$resize_ratio), $this->height);
                imagejpeg($newimg, $this->dstimg, $quality);
            }
            if($ratio<$resize_ratio) { //宽度优先
                $newimg = imagecreatetruecolor($this->resize_width,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, (($this->width)/$resize_ratio));
                imagejpeg($newimg, $this->dstimg, $quality);
            }
        }  else { //不裁图
            if($ratio>=$resize_ratio) {
                $newimg = imagecreatetruecolor($this->resize_width,($this->resize_width)/$ratio);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, $this->resize_width, ($this->resize_width)/$ratio, $this->width, $this->height);
                imagejpeg($newimg, $this->dstimg, $quality);
            }
            if($ratio<$resize_ratio) {
                $newimg = imagecreatetruecolor(($this->resize_height)*$ratio,$this->resize_height);
                imagecopyresampled($newimg, $this->im, 0, 0, 0, 0, ($this->resize_height)*$ratio, $this->resize_height, $this->width, $this->height);
                imagejpeg($newimg, $this->dstimg, $quality);
            }
        }
	
		if(file_exists($this->srcimg)) {
			@unlink($this->srcimg);
		}
		
		 ImageDestroy($this->im);
		 return true;
    }
	
    //初始化图象
    function init() {
		global $_G;

		$this->type = strtolower(substr(strrchr($this->srcimg,"."),1));
		$dest = $this->dstimg.'.jpg';

		if(strpos($this->srcimg, $_G['siteurl']) === 0) {
			copy(str_replace($_G['siteurl'], DISCUZ_ROOT, $this->srcimg), $dest);
		} else {
			$data = get_url_contents($this->srcimg);
			file_put_contents($dest, $data);
		}
		$imginfo = getimagesize($dest);
		if(!$imginfo) {
			if(file_exists($dest)) {
				@unlink($dest);
			}
			return;
		}
		
		$this->srcimg = $dest;
		$this->mime = $imginfo['mime'];
		$this->width = $imginfo[0];
        $this->height = $imginfo[1];
	
		switch($this->mime) {
			case 'image/jpeg':
				$this->im = imagecreatefromjpeg($this->srcimg);
				break;
			case 'image/gif':
				 $this->im = imagecreatefromgif($this->srcimg);
				break;
			case 'image/png':
				$this->im = imagecreatefrompng($this->srcimg);
				break;
		}
		return 1;
    }
	
    //图象目标地址
    function dst_img($dstpath) {
        $full_length  = strlen($this->srcimg);
        $type_length  = strlen($this->type);
        $name_length  = $full_length-$type_length;
        $name         = substr($this->srcimg,0,$name_length-1);
        $this->dstimg = $dstpath;
    }
}
?>