<?php
namespace PharIo\Phive;

class KeyService {

    /**
     * @var KeyDownloader
     */
    private $keyDownloader;

    /**
     * @var KeyImporter
     */
    private $keyImporter;

    /**
     * @var Cli\Output
     */
    private $output;

    /**
     * @var Cli\Input
     */
    private $input;

    /**
     * @var KeyIdCollection
     */
    private $trusted;

    /**
     * @param KeyDownloader   $keyDownloader
     * @param KeyImporter     $keyImporter
     * @param KeyIdCollection $trusted
     * @param Cli\Output      $output
     * @param Cli\Input       $input
     */
    public function __construct(
        KeyDownloader $keyDownloader,
        KeyImporter $keyImporter,
        KeyIdCollection $trusted,
        Cli\Output $output,
        Cli\Input $input
    ) {
        $this->keyDownloader = $keyDownloader;
        $this->keyImporter = $keyImporter;
        $this->output = $output;
        $this->input = $input;
        $this->trusted = $trusted;
    }

    /**
     * @param string $keyId
     * @param array  $knownFingerprints
     *
     * @return mixed
     */
    public function importKey($keyId, array $knownFingerprints) {
        $key = $this->downloadKey($keyId);

        if (!empty($knownFingerprints) && !in_array($key->getFingerprint(), $knownFingerprints)) {
            $this->output->writeWarning(
                "This is NOT a key that has been used to install previous versions of this PHAR.\n"
                . "           While this can be perfectly valid (maybe the maintainer switched to a new key),\n"
                . "           please make sure this key belongs to the maintainer of the PHAR you are going to install."
            );
        }

        $this->output->writeText("\n" . $key->getInfo() . "\n\n");

        if (!$this->allowedToImport($key)) {
            $this->output->writeError(sprintf('User declined import of key %s', $key->getId()));
            return new KeyImportResult(0);
        }

        return $this->keyImporter->importKey($key->getKeyData());
    }

    /**
     * @param $keyId
     *
     * @return PublicKey
     */
    private function downloadKey($keyId) {
        $this->output->writeInfo(sprintf('Downloading key %s', $keyId));

        return $this->keyDownloader->download($keyId);
    }

    /**
     * @param PublicKey $key
     *
     * @return bool
     */
    private function allowedToImport(PublicKey $key) {
        return $this->trusted->has($key->getId()) || $this->input->confirm('Import this key?', false);
    }

}
