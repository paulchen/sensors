package at.rueckgr.android.ipwe.data;

import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

import at.rueckgr.android.ipwe.SensorsApplication;

public class Status {
	private List<Sensor> sensors;
	private SensorsApplication application;

	public Status(SensorsApplication application) {
		this.application = application;
	}
	
	public void update() throws SensorsException {
		
		String url = application.getSettingsURL() + "?action=status";
		
		InputStream inputStream = application.executeHttpGet(url);
		DocumentBuilderFactory documentBuilderFactory = DocumentBuilderFactory.newInstance();
		DocumentBuilder documentBuilder;
		try {
			documentBuilder = documentBuilderFactory.newDocumentBuilder();
		}
		catch (ParserConfigurationException e) {
			throw new SensorsException(e);
		}
		try {
			Document document = documentBuilder.parse(inputStream);
			NodeList nodes = document.getChildNodes();
			
			sensors = new ArrayList<Sensor>();
			boolean nodeProcessed = false;
			for(int a=0; a<nodes.getLength(); a++) {
				Node node = nodes.item(a);
				if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("sensors")) {
					if(nodeProcessed) {
						throw new SensorsException("Duplicate <sensors> as root element in XML input from API.");
					}
					nodeProcessed = true;
					processNode(node);
				}
			}
		}
		catch (SAXException e) {
			throw new SensorsException(e);
		}
		catch (IOException e) {
			throw new SensorsException(e);
		}
	}

	private void processNode(Node parentNode) throws SensorsException {
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("sensor")) {
				sensors.add(new Sensor(node, this));
			}
		}
	}

	public List<Sensor> getSensors() {
		return sensors;
	}
	
	@Override
	public String toString() {
		return "[Status:sensors=" + sensors.toString() + "]";
	}

	public Integer getStateCount(State state) {
		int count = 0;
		
		for(Sensor sensor : sensors) {
			count += sensor.getStateCount(state);
		}
		
		return count;
	}

	public List<Measurement> getMeasurements() {
		List<Measurement> measurements = new ArrayList<Measurement>();
		
		for(Sensor sensor : sensors) {
			measurements.addAll(sensor.getMeasurements());
		}
		
		return measurements;
	}

	public Map<String, Integer> getStateCounts() {
		Map<String, State> states = application.getStates();
		Map<String, Integer> stateCounts = new HashMap<String, Integer>();
		for(String stateName : states.keySet()) {
			stateCounts.put(stateName, getStateCount(states.get(stateName)));
		}
		return stateCounts;
	}

	public SensorsApplication getApplication() {
		return application;
	}
}
