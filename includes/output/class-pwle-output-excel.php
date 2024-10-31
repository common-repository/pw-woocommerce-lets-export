<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once( 'spout-2.7.3/src/Spout/Autoloader/autoload.php' );
use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\Style\StyleBuilder;

if ( ! class_exists( 'PWLE_Output_Excel' ) ) :

class PWLE_Output_Excel extends PWLE_Output {

    public  $output_filename;

    private $writer;
    private $sheets = array();
    private $current_class = '';
    private $current_row = 1;

    function __construct() {
        $this->name = 'Excel';
        $this->title = 'Export to Excel';
        $this->icon = 'fa-file-excel-o';
        $this->color = '#1B7449';
        $this->extension = '.xlsx';
    }

    function add_row( $input_class, $row ) {
        if ( !isset( $this->writer ) ) {
            $this->create_writer();
        }

        $class_name = get_class( $input_class );

        if ( !isset( $this->sheets[ $class_name ] ) ) {
            $this->sheets[ $class_name ] = $this->create_sheet( $input_class );

            $header_style = (new StyleBuilder())->setFontBold()->setShouldWrapText(false)->build();
            $this->writer->addRowWithStyle( $row, $header_style );

        } else {
            $style = (new StyleBuilder())->setShouldWrapText(false)->build();
            $this->writer->addRowWithStyle( $row, $style );
        }
    }

    function finish() {
        if ( empty( $this->writer ) ) {
            return;
        }

        $this->writer->close();
    }

    function create_writer() {

        $this->writer = WriterFactory::create( Type::XLSX );
        $this->writer->openToFile( $this->output_filename );
    }

    function create_sheet( &$input_class ) {
        if ( count( $this->sheets ) == 0 ) {
            $sheet = $this->writer->getCurrentSheet();
        } else {
            $sheet = $this->writer->addNewSheetAndMakeItCurrent();
        }

        $sheet->setName( $input_class->title_plural );

        return $sheet;
    }

    function send_file() {
        if ( file_exists( $this->output_filename ) ) {
            // Redirect output to a client’s web browser (Excel2007)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $this->get_filename() . '"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
            header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header ('Pragma: public'); // HTTP/1.0

            readfile( $this->output_filename );
            unlink( $this->output_filename );
            exit;
        } else {
            wp_die( "Temporary file not found ({$this->output_filename})." );
        }
    }
}

global $pw_lets_export;
$pw_lets_export->output_classes[] = new PWLE_Output_Excel();

endif;

?>