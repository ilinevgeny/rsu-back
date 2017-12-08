<?php


namespace rsu\controllers\admin;


use rsu\models\Cities;
use rsu\models\Houses;
use rsu\models\Streets;
use rsu\Service\Utils;

class HousesController extends ControllerBase
{
	const LIMIT = 2;
	const OFFSET = 10;

	public $errorMsg = [];

	public function initialize()
	{
		$this->view->setMainView('admin/main');
		$this->view->setVar('menu', true);
	}

	public function indexAction()
	{
		$this->view->pick("admin/houses");

		$amount = $this->request->get('amount', null, self::OFFSET);
		$page = $this->request->get('page', null, 1);
		$offset = (($amount * ($page - 1))) . ', ' . $amount . '';


		$records = Houses::find(
			[
				'limit'  => (int) $amount,
				'offset' => (int) ($amount * ($page - 1))
			]
		);


		$total = Houses::find()->count();

		$tableRecords = '<table class="houses-table table table-bordered table-hover table-sm table-responsive-sm">
                <tr>
                    <th>ID</th>
                    <th>Адрес</th>
                    <th>Счет</th>
                </tr>';

		foreach ($records as $record) {
			$address = Cities::findById($record->city_id)->name . ' ' . Streets::findById($record->street_id)->name . ' ' . $record->number;
			$tableRecords .= '<tr data="houses" id="' . $record->id . '" class="row-record">
					<td class="id-column">' . $record->id . '</td>
                    <td class="purpose-column"><a href="/admin/houses/edit/' . $record->id . '">' . $address . '</a></td>
                    <td>' . $record->account_id . '</td>
                    </tr>';
		}
		$tableRecords .= '</table>';

		$pageCount = ceil($total / $amount);
		$paging = '';
		$pagingPerItem = self::LIMIT;
		$pageOffset = ($page <= $pagingPerItem * (ceil($page / $pagingPerItem))) ? $pagingPerItem * (ceil($page / $pagingPerItem)) : 1;
		$i = ($pagingPerItem * (ceil($page / $pagingPerItem) - 1)) + 1;
		if ($page > $pagingPerItem) {
			$paging .= '<a class="paging-nav" href="?page=1&amount=' . $amount . '">1</a><a class="paging-nav" href="?page=' . ($i - 1) . '&amount=' . $amount . '">...</a>';
		}
		for ($i; $i <= $pageOffset + 1 && $i <= $pageCount; $i++) {
			if ($i == $page) {
				$paging .= '<span>' . $i . '</span>';
			} else {
				if ($i <= ($pageOffset)) {
					$paging .= '<a class="paging-nav" href="?page=' . $i . '&amount=' . $amount . '">' . $i . '</a>';
				} else {
					$paging .= '<a class="paging-nav" href="?page=' . $i . '&amount=' . $amount . '">...</a>';
					$paging .= '<a class="paging-nav" href="?page=' . $pageCount . '&amount=' . $amount . '">' . $pageCount . '</a>';
					break;
				}
			}
		}
		$paging = '<div class="paging">' . $paging . '</div>';
		$this->view->setVar('paging', $paging);
		$this->view->setVar('housesActive', 'active');
		$this->view->setVar('tableRecords', $tableRecords);
	}

	public function editAction($id)
	{
		$this->view->pick("admin/housesedit");
		$record = Houses::findById($id);

		if ($this->request->isPost()) {
			$data = $this->request->getPost();
			$record->account_id = $data['account'];
			if($record->update()) {
				$this->flash->success('Запись обновлена');
			} else {
				$this->flash->error('Запись не была обновлена');
			};
//			$this->view->setVar('purpose', [$data['purpose']);
		}
		$this->view->setVar('house', ['city' => Cities::findById($record->city_id)->name,
		                              'street' => Streets::findById($record->street_id)->name,
		                              'number' => $record->number,
		                              'account' => $record->account_id]);
		$this->view->setVar('img', '/' . $this->config->common->img . '/' . $record->photo_url);


	}

	public function addAction()
	{
		$this->view->pick("admin/housesedit");
		$this->view->setVar('house', ['city' => '',
		                              'street' => '',
		                              'number' => '',
		                              'account' => '']);
		$this->view->setVar('img', '/' . $this->config->common->img . '/' . 'sample.jpeg');
		if ($this->request->isPost())
		{
			$data = $this->request->getPost();

			$house = new Houses();
//			print_r($data);
			$city = Cities::find(
				 ['conditions' => 'name LIKE  ?0',
				 'bind'       => ['%' . mb_convert_encoding(trim($data['city']), 'UTF-8' ) . '%']
				]);
			$cityArr = $streetArr = [];
			if ($city->count() > 0) {
				$this->flash->notice('Город  ' . $city[0]->name . ' уже существует');
				$cityArr['id'] = $city[0]->id;
				$cityArr['name'] = $city[0]->name;

			} else {
				$addCity = new Cities();
				$addCity->name = mb_convert_encoding(trim($data['city']), 'UTF-8' );
				$addCity->region_id = 1;
				$addCity->save();
				$cityArr['id'] = $addCity->id;
				$cityArr['name'] = $addCity->name;
			}
			$street = Streets::find(
				['conditions' => 'name LIKE ?0 AND city_id = ?1',
					'bind' => [mb_convert_encoding(trim($data['street']), 'UTF-8' ),
						$cityArr['id']
					]]
			);
			if ($street->count() > 0) {
				$this->flash->notice('Улица  ' . $street[0]->name . ' уже существует');
				$streetArr['id'] = $street[0]->id;
				$streetArr['name'] = $street[0]->name;
			} else {
				$street = new Streets();
				$street->name = mb_convert_encoding(trim($data['street']), 'UTF-8' );
				$street->city_id = $cityArr['id'];
				$street->create();
				$streetArr['id'] = $street->id;
				$streetArr['name'] = $street->name;
			}
			$house->city_id = $cityArr['id'];
			$house->street_id = $streetArr['id'];
			$house->number = mb_convert_encoding(trim($data['number']), 'UTF-8' );
			$house->account_id = mb_convert_encoding(trim($data['account']), 'UTF-8' );
			$code = Utils::getUniqueCode($cityArr['name'] . ' ' . $streetArr['name'] . ' ' . $house->number);
//			print_r($code); exit;
//			var_dump($_FILES);
			if ($this->request->hasFiles(true) == true) {
				$url = $this->setHouseImage($this->request->getUploadedFiles(), $code);
				if (!$url) {
					foreach ($this->errorMsg as $message) {
						$this->flash->error($message);
					}
					$house->photo_url = '';
				} else {
					$house->photo_url = $url;
				}
			} else {
				echo "file is haven't";
				$house->photo_url = '';
			}

//			print_r($house->photo_url);

			if($house->save()) {
				$this->flash->success('Запись обновлена');
			} else {
				$this->flash->error('Запись не была обновлена');
			};

		}
	}

	private function setHouseImage($fileupload, $code)
	{
		$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->config->common->img . '/';
		$width = 160;
		$height = 160;

		$extensions = [
			"image/jpeg" => 'jpeg',
			"image/jpg" => 'jpg',
			"image/png" => 'png'
		];

		$allowed = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
//		print_r(ini_get('upload_max_filesize')); exit;
		foreach ($fileupload as $file) {
//			print_r("filesize " . $file->getSize());
//			echo "<br>";
//			print_r($this->returnBytes(ini_get('upload_max_filesize')));
//			echo "<br>";
			if ($file->getSize() > $this->returnBytes(ini_get('upload_max_filesize'))) {
				$this->errorMsg[] = 'Файл слишком большой';
				return false;
			}
//			exit;
//			$firstSymbol = substr($code, 0, 1);
//			if (!is_dir($uploadDir . $firstSymbol)) {
//				if (mkdir($uploadDir . $firstSymbol)) {
//					chmod($uploadDir . $firstSymbol, 0644);
//				} else {
//					$this->errorMsg[] = "Невозможно создать каталог";
//				}
//			}
			$fileType = exif_imagetype($file->getTempName());
			if (in_array($fileType, $allowed)) {
				$photoPath = $code . '.' . $extensions[$file->getType()];

				switch ($fileType) {
					case IMAGETYPE_GIF:
						$img = imagecreatefromgif($file->getTempName());
						break;
					case IMAGETYPE_JPEG:
						$img = imagecreatefromjpeg($file->getTempName());
						break;
					case IMAGETYPE_PNG:
						$img = imagecreatefrompng($file->getTempName());
						break;
				}

				$sizeX = getimagesize($file->getTempName())[0];
				$sizeY = getimagesize($file->getTempName())[1];

				if ($sizeX < $width || $sizeY < $height) {
					$this->errorMsg[] = "Размер изображения слишком мал";
					return false;
				}

				if ($sizeX >= $sizeY) {
					$ratio = $sizeY / $height;
					$sizeXTmp = $sizeX / $ratio;
					$sizeYTmp = $height;
					if ($sizeXTmp <= $width) {
						$crop = $sizeXTmp;
					} else {
						$crop = $sizeXTmp - ($sizeXTmp - $width);
					}
					$resizeImgResult = imagecreatetruecolor($crop, $sizeYTmp);
				} elseif ($sizeY > $sizeX) {
					$ratio = $sizeX / $width;
					$sizeYTmp = $sizeY / $ratio;
					$sizeXTmp = $width;
					if ($sizeYTmp <= $height) {
						$crop = $sizeYTmp;
					} else {
						$crop = $sizeYTmp - ($sizeYTmp - $height);
					}
					$resizeImgResult = imagecreatetruecolor($sizeXTmp, $crop);
				} elseif ($sizeX == $width && $sizeY == $height) {
					$sizeXTmp = $width;
					$sizeYTmp = $height;
					$resizeImgResult = imagecreatetruecolor($sizeXTmp, $sizeYTmp);
				}

				$resizeImg = imagecreatetruecolor($sizeXTmp, $sizeYTmp);

				imagecopyresampled($resizeImg, $img, 0, 0, 0, 0, $sizeXTmp, $sizeYTmp, $sizeX, $sizeY);

				imagecopy($resizeImgResult, $resizeImg, 0, 0, 0, 0, $width, $height);

				switch ($fileType) {
					case IMAGETYPE_GIF:
						imagegif($resizeImgResult, $uploadDir . $photoPath);
						break;
					case IMAGETYPE_JPEG:
						imagejpeg($resizeImgResult, $uploadDir . $photoPath, 100);
						break;
					case IMAGETYPE_PNG:
						imagepng($resizeImgResult, $uploadDir . $photoPath, 4);
						break;
				}
				chmod($uploadDir . $photoPath, 0644);
				return $photoPath;
			} else {
				$this->errorMsg[] = "Ваш файл не является изображением";
			}
		}
		return false;
	}

	public function returnBytes($val)
	{
		$val = trim($val);

		$last = strtolower($val[strlen($val) - 1]);
		$val = substr($val, 0, strlen($val) - 1);
//		print_r("last " . $last);
//		print_r("val_1 " . $val);
		switch ($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
//		print_r('val ' . $val);
		return $val;
	}
}