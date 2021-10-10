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
        $neuralLinks = $this->decodeGenome($genome);

        $neuralNetwork = [
            "SENSOR" => [],
            "INTERNAL" => [],
            "TRIGGER" => [],
        ];

        foreach ($neuralLinks as $neuralLink) {
            if (!array_key_exists($neuralLink["EMITTER_ID"], $neuralNetwork[$neuralLink["EMITTER_TYPE"]])) {
                $neuralNetwork[$neuralLink["EMITTER_TYPE"]][$neuralLink["EMITTER_ID"]] = [
                    "CONNECTIONS" => 1,
                    "OUTPUTS" => [
                        [
                            "RECEIVER_ID" => $neuralLink["RECEIVER_ID"],
                            "LINK_STRENGTH" => $neuralLink["LINK_STRENGTH"],
                        ],
                    ],
                ];
            } else {
                $neuralNetwork[$neuralLink["EMITTER_TYPE"]][$neuralLink["EMITTER_ID"]]["CONNECTIONS"]++;

                $neuralNetwork[$neuralLink["EMITTER_TYPE"]][$neuralLink["EMITTER_ID"]]["OUTPUTS"][] = [
                    "RECEIVER_ID" => $neuralLink["RECEIVER_ID"],
                    "LINK_STRENGTH" => $neuralLink["LINK_STRENGTH"],
                ];
            }

            if (!array_key_exists($neuralLink["RECEIVER_ID"], $neuralNetwork[$neuralLink["RECEIVER_TYPE"]])) {
                $neuralNetwork[$neuralLink["RECEIVER_TYPE"]][$neuralLink["RECEIVER_ID"]] = [
                    "CONNECTIONS" => 1,
                    "INPUTS" => [
                        [
                            "EMITTER_ID" => $neuralLink["EMITTER_ID"],
                            "LINK_STRENGTH" => $neuralLink["LINK_STRENGTH"],
                        ],
                    ],
                ];
            } else {
                $neuralNetwork[$neuralLink["RECEIVER_TYPE"]][$neuralLink["RECEIVER_ID"]]["CONNECTIONS"]++;

                $neuralNetwork[$neuralLink["RECEIVER_TYPE"]][$neuralLink["RECEIVER_ID"]]["INPUTS"][] = [
                    "EMITTER_ID" => $neuralLink["EMITTER_ID"],
                    "LINK_STRENGTH" => $neuralLink["LINK_STRENGTH"],
                ];
            }
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

        return $this->convertBinaryData(array_combine(self::GENE_DATA_KEYS, $splitGeneData));
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