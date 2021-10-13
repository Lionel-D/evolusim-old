import { Controller } from 'stimulus';
import {CircularLayout} from "gojs";

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="neuralgraph" attribute will cause
 * this controller to be executed. The name "neuralgraph" comes from the filename:
 * neuralgraph_controller.js -> "neuralgraph"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
    connect() {
        this.element.style.width = this.element.offsetWidth + "px";
        this.element.style.height = this.element.offsetHeight + "px";

        let $ = go.GraphObject.make;
        let neuralGraph = $(go.Diagram, this.element.id);

        neuralGraph.layout = $(go.CircularLayout,
            {
                spacing: 50,
                arrangement: CircularLayout.Packed,
                direction: CircularLayout.BidirectionalLeft,
                sorting: CircularLayout.Ascending,
            }
        );

        neuralGraph.nodeTemplate = $(go.Node, "Auto",
            $(go.Shape, "Ellipse", {stroke: "rgba(0,0,0,0.25)"}, new go.Binding("fill", "color")),
            $(go.TextBlock, {font: "8pt sans-serif", stroke: "white", margin: 5}, new go.Binding("text", "text")),
        );

        neuralGraph.linkTemplate = $(go.Link,
            {curve: go.Link.Bezier},
            $(go.Shape, new go.Binding("stroke", "color")),
            $(go.Shape, {strokeWidth: 0, toArrow: "Standard"}, new go.Binding("fill", "color")),
        );

        console.log(nodeDataArray);
        console.log(linkDataArray);

        neuralGraph.model = new go.GraphLinksModel(nodeDataArray, linkDataArray);
    }
}
