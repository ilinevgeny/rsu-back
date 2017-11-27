<?php


namespace rsu\controllers\admin;


use rsu\models\TochkaStatementDays;
use rsu\models\TochkaStatementRecords;

class StatementsController extends ControllerBase
{
	const LIMIT = 2;
	const OFFSET = 10;
    public function initialize()
    {
        $this->view->setMainView('admin/main');
        $this->view->setVar('menu', true);
    }

    public function listAction()
    {

	    $amount = $this->request->get('amount', null, self::OFFSET);
	    $page = $this->request->get('page', null, 1);
	    $offset = (($amount * ($page - 1))) . ', ' . $amount . '';

	    $this->view->pick("admin/statement");
        $records = TochkaStatementRecords::find(
	        [
		        'limit'  => (int) $amount,
		        'offset' => (int) ($amount * ($page - 1))
	        ]
        );
        $total = TochkaStatementRecords::find()->count();
        $tableRecords = '<table class="statement-table table table-bordered table-hover table-sm table-responsive-sm">
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Описание</th>
                    <th>Контрагент</th>
                </tr>';
        foreach ($records as $record) {
            $date = date('d.m.Y', strtotime(TochkaStatementDays::findById($record->days_id)->date));
            $tableRecords .= '<tr id="' . $record->id . '" class="row-record"><td class="id-column">' . $record->id . '</td><td>' . $date . '</td>
                    <td>' . $record->sum . '</td>
                    <td class="purpose-column"><a href="/admin/statements/edit/' . $record->id . '">' . $record->purpose . '</a></td>
                    <td>' . $record->counterparty . '</td>
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
        $this->view->setVar('tableRecords', $tableRecords);
        $this->view->setVar('statementActive', 'active');
        $this->view->setVar('mainActive', '');
    }

    public function editAction($id)
    {
        $record = TochkaStatementRecords::findById($id);
        $this->view->setVar('purpose', $record->purpose);
        $this->view->pick("admin/statementedit");
        if ($this->request->isPost()) {
                $data = $this->request->getPost();
            $record->purpose = $data['purpose'];
            if($record->update()) {
	            $this->flash->success('Запись обновлена');
            } else {
	            $this->flash->error('Запись не была обновлена');
            };
            $this->view->setVar('purpose', $data['purpose']);
        }
	    $this->view->setVar('statementActive', 'active');
	    $this->view->setVar('mainActive', '');
    }
}