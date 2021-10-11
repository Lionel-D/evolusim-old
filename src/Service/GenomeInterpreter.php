<?php

namespace App\Service;

class GenomeInterpreter
{
    // TODO: maybe these constants should be elsewhere...
    const GENE_CODE_PATTERN = "/([0-9])([0-9]{7})([0-9])([0-9]{7})([0-9]{16})$/";
    const GENE_DATA_KEYS = ["EMITTER_TYPE", "EMITTER_ID", "RECEIVER_TYPE", "RECEIVER_ID", "LINK_STRENGTH"];
    const EMITTER_TYPES = ["INTERNAL", "SENSOR"];
    const RECEIVER_TYPES = ["INTERNAL", "TRIGGER"];
    // TODO: The neurons pool should be generated based on the simulation parameters
    const NEURONS_POOL = [
        "SENSOR" => ["SENSOR 1", "SENSOR 2", "SENSOR 3", "SENSOR 4"],
        "INTERNAL" => ["INTERNAL 1", "INTERNAL 2"],
        "TRIGGER" => ["TRIGGER 1", "TRIGGER 2", "TRIGGER 3"],
    ];

    public function buildNeuralNetwork(string $genome): array
    {
        $genomeData = $this->decodeGenome($genome);

        $neuralNetwork = [
            "SENSOR" => [],
            "INTERNAL" => [],
            "TRIGGER" => [],
        ];

        foreach ($genomeData as $neuralLink) {
            $this->updateNeuralLinking(
                $neuralNetwork,
                $neuralLink["EMITTER_TYPE"],
                $neuralLink["EMITTER_ID"],
                $neuralLink["RECEIVER_ID"],
                $neuralLink["LINK_STRENGTH"],
                "OUTPUTS"
            );

            $this->updateNeuralLinking(
                $neuralNetwork,
                $neuralLink["RECEIVER_TYPE"],
                $neuralLink["RECEIVER_ID"],
                $neuralLink["EMITTER_ID"],
                $neuralLink["LINK_STRENGTH"],
                "INPUTS"
            );
        }

        return $neuralNetwork;
    }

    private function updateNeuralLinking(&$neuralNetwork, $neuronType, $neuronId, $linkedNeuronId, $linkStrength, $signalType): array
    {
        $linkedNeuronKeys = [
            "INPUTS" => "EMITTER_ID",
            "OUTPUTS" => "RECEIVER_ID",
        ];

        if (!array_key_exists($neuronId, $neuralNetwork[$neuronType])) {
            $neuralNetwork[$neuronType][$neuronId] = [
                "CONNECTIONS" => 1,
                $signalType => [
                    [
                        $linkedNeuronKeys[$signalType] => $linkedNeuronId,
                        "LINK_STRENGTH" => $linkStrength,
                    ],
                ],
            ];
        } else {
            $neuralNetwork[$neuronType][$neuronId]["CONNECTIONS"]++;

            $neuralNetwork[$neuronType][$neuronId][$signalType][] = [
                $linkedNeuronKeys[$signalType] => $linkedNeuronId,
                "LINK_STRENGTH" => $linkStrength,
            ];
        }

        return $neuralNetwork;
    }

    private function decodeGenome(string $genome): array
    {
        $hexGeneList = explode("|", $genome);

        $genomeData = [];

        foreach ($hexGeneList as $hexGeneCode) {
            $genomeData[] = $this->decodeGene($hexGeneCode);
        }

        return $genomeData;
    }

    private function decodeGene(string $hexGeneCode): array
    {
        if (!ctype_xdigit($hexGeneCode)) {
            throw new \InvalidArgumentException("Argument $hexGeneCode is not a valid hexadecimal code.");
        }

        $binGeneCode = $this->convertHexToBinary($hexGeneCode);

        $patternMatch = preg_match(self::GENE_CODE_PATTERN, $binGeneCode, $splitGeneData);

        if ($patternMatch != 1) {
            throw new \UnexpectedValueException("Gene binary for $hexGeneCode doesn't match the expected pattern.");
        }

        array_shift($splitGeneData);

        $binGeneData = array_combine(self::GENE_DATA_KEYS, $splitGeneData);

        return $this->convertBinaryData($binGeneData);
    }

    private function convertBinaryData(array $geneData): array
    {
        foreach ($geneData as $key => $binaryValue) {
            switch ($key) {
                case "EMITTER_TYPE":
                    $geneData["EMITTER_TYPE"] = self::EMITTER_TYPES[$binaryValue];
                    break;
                case "EMITTER_ID":
                    $emitterKey = bindec($binaryValue) % count(self::NEURONS_POOL[$geneData["EMITTER_TYPE"]]);
                    $geneData["EMITTER_ID"] = self::NEURONS_POOL[$geneData["EMITTER_TYPE"]][$emitterKey];
                    break;
                case "RECEIVER_TYPE":
                    $geneData["RECEIVER_TYPE"] = self::RECEIVER_TYPES[$binaryValue];
                    break;
                case "RECEIVER_ID":
                    $receiverKey = bindec($binaryValue) % count(self::NEURONS_POOL[$geneData["RECEIVER_TYPE"]]);
                    $geneData["RECEIVER_ID"] = self::NEURONS_POOL[$geneData["RECEIVER_TYPE"]][$receiverKey];
                    break;
                case "LINK_STRENGTH":
                    $linkStrength = unpack("s", pack("s", bindec($binaryValue)))[1];
                    $geneData["LINK_STRENGTH"] = round($linkStrength / 8192, 1);
                    break;
            }
        }

        return $geneData;
    }

    private function convertHexToBinary(string $hexadecimal): string
    {
        $binary = "";

        foreach (str_split($hexadecimal, 1) as $hexDigit) {
            $binary .= sprintf("%04b", base_convert($hexDigit, 16, 2));
        }

        return $binary;
    }
}