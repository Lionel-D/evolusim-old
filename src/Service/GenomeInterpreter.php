<?php

namespace App\Service;

class GenomeInterpreter
{
    // TODO: maybe these constants should be elsewhere...
    const GENE_CODE_PATTERN = "/([0-9])([0-9]{7})([0-9])([0-9]{7})([0-9]{16})$/";
    const GENE_DATA_KEYS = ["EMITTER_TYPE", "EMITTER_ID", "RECEIVER_TYPE", "RECEIVER_ID", "LINK_STRENGTH"];
    const EMITTER_TYPES = ["INTERNAL", "SENSOR"];
    const RECEIVER_TYPES = ["INTERNAL", "TRIGGER"];
    const NEURONS_COLORS = ["SENSOR" => "#198754", "INTERNAL" => "#0dcaf0", "TRIGGER" => "#dc3545"];
    // TODO: The neurons pool should be generated based on the simulation parameters
    const NEURONS_POOL = [
        "SENSOR" => ["SENSOR 1", "SENSOR 2", "SENSOR 3", "SENSOR 4"],
        "INTERNAL" => ["INTERNAL 1", "INTERNAL 2"],
        "TRIGGER" => ["TRIGGER 1", "TRIGGER 2", "TRIGGER 3"],
    ];

    public function getNeuralGraphData(array $neuralNetwork): array
    {
        $nodeData = [];
        $linkData = [];

        $neuronKeys = [];

        foreach ($neuralNetwork as $neuronsType => $neuronsList) {
            $neuronColor = self::NEURONS_COLORS[$neuronsType];

            foreach ($neuronsList as $neuron => $neuronData) {
                if (!in_array($neuron, $neuronKeys)) {
                    $neuronKeys[] = $neuron;
                }

                $key = array_search($neuron, $neuronKeys);

                $nodeData[] = ["key" => $key, "text" => $neuron, "color" => $neuronColor];

                if (array_key_exists("INPUTS", $neuronData)) {
                    foreach ($neuronData["INPUTS"] as $inputData) {
                        if (!in_array($inputData["EMITTER_ID"], $neuronKeys)) {
                            $neuronKeys[] = $inputData["EMITTER_ID"];
                        }

                        $fromKey = array_search($inputData["EMITTER_ID"], $neuronKeys);

                        $linkData[] = [
                            "from" => $fromKey,
                            "to" => $key,
                            "width" => 1 + abs($inputData["LINK_STRENGTH"]),
                            "color" => $inputData["LINK_STRENGTH"] > 0 ? "#75b798" : "#ea868f",
                        ];
                    }
                }
            }
        }

        return ["node_data" => $nodeData, "link_data" => $linkData];
    }

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

        return $this->pruneNeuralNetwork($neuralNetwork);
    }

    private function pruneNeuralNetwork($neuralNetwork)
    {
        $triggeringNeurons= $this->getTriggeringNeuronsList($neuralNetwork);

        foreach ($neuralNetwork as &$neuronList) {
            $this->pruneNeuronsList($neuronList, $triggeringNeurons);
        }

        return $neuralNetwork;
    }

    private function getTriggeringNeuronsList($neuralNetwork)
    {
        $triggeringNeurons= [];

        // First we iterate over TRIGGER neurons as they are by definition useful
        foreach ($neuralNetwork["TRIGGER"] as $neuronId => $neuronData) {
            // We add the TRIGGER neuron itself
            $triggeringNeurons[] = $neuronId;

            // Then we add each of its inputs since they define the TRIGGER neuron action
            foreach ($neuronData["INPUTS"] as $input) {
                if (!in_array($input["EMITTER_ID"], $triggeringNeurons)) {
                    $triggeringNeurons[] = $input["EMITTER_ID"];
                }
            }
        }

        // Next we iterate over INTERNAL neurons as it's here that there's likely useless paths
        // (meaning paths that doesn't end impacting TRIGGER neurons)
        foreach ($neuralNetwork["INTERNAL"] as $neuronId => $neuronData) {
            // First we check if the INTERNAL neuron isn't in the effective list BUT has output links
            if (!in_array($neuronId, $triggeringNeurons) && array_key_exists("OUTPUTS", $neuronData)) {
                // If so, we check if any of the output links goes into a neuron of the effective list
                foreach ($neuronData["OUTPUTS"] as $output) {
                    if (in_array($output["RECEIVER_ID"], $triggeringNeurons)) {
                        // If that's the case, we add the INTERNAL neuron to the effective list
                        $triggeringNeurons[] = $neuronId;
                        break;
                    }
                }
            }

            // Then we check if the INTERNAL neuron is part of the effective list
            // (either from the previous check or the TRIGGER loop before that)
            // AND if it has input links
            if (in_array($neuronId, $triggeringNeurons) && array_key_exists("INPUTS", $neuronData)) {
                // If so, we add each of its inputs since they define the INTERNAL neuron action
                foreach ($neuronData["INPUTS"] as $input) {
                    if (!in_array($input["EMITTER_ID"], $triggeringNeurons)) {
                        $triggeringNeurons[] = $input["EMITTER_ID"];
                    }
                }
            }
        }

        return $triggeringNeurons;
    }

    private function pruneNeuronsList(&$neuronList, $triggeringNeurons)
    {
        foreach ($neuronList as $neuronId => &$neuronData) {
            if (!in_array($neuronId, $triggeringNeurons)) {
                unset($neuronList[$neuronId]);
            } else {
                $inputConnections = 0;
                $outputConnections = 0;

                if (array_key_exists("INPUTS", $neuronData)) {
                    foreach ($neuronData["INPUTS"] as $key => $input) {
                        if (!in_array($input["EMITTER_ID"], $triggeringNeurons)) {
                            unset($neuronData["INPUTS"][$key]);
                        }
                    }

                    $inputConnections = count($neuronData["INPUTS"]);

                    if ($inputConnections === 0) {
                        unset($neuronData["INPUTS"]);
                    }
                }

                if (array_key_exists("OUTPUTS", $neuronData)) {
                    foreach ($neuronData["OUTPUTS"] as $key => $output) {
                        if (!in_array($output["RECEIVER_ID"], $triggeringNeurons)) {
                            unset($neuronData["OUTPUTS"][$key]);
                        }
                    }

                    $outputConnections = count($neuronData["OUTPUTS"]);

                    if ($outputConnections === 0) {
                        unset($neuronData["OUTPUTS"]);
                    }
                }

                $neuronData["CONNECTIONS"] = $inputConnections + $outputConnections;
            }
        }
    }

    private function updateNeuralLinking(&$neuralNetwork, $neuronType, $neuronId, $linkedNeuronId, $linkStrength, $signalType): array
    {
        $linkedNeuronKey = [
            "INPUTS" => "EMITTER_ID",
            "OUTPUTS" => "RECEIVER_ID",
        ];

        if (!array_key_exists($neuronId, $neuralNetwork[$neuronType])) {
            $neuralNetwork[$neuronType][$neuronId] = [
                "CONNECTIONS" => 1,
                $signalType => [
                    [
                        $linkedNeuronKey[$signalType] => $linkedNeuronId,
                        "LINK_STRENGTH" => $linkStrength,
                    ],
                ],
            ];
        } else {
            $neuralNetwork[$neuronType][$neuronId]["CONNECTIONS"]++;

            $neuralNetwork[$neuronType][$neuronId][$signalType][] = [
                $linkedNeuronKey[$signalType] => $linkedNeuronId,
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