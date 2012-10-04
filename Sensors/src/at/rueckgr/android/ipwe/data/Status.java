package at.rueckgr.android.ipwe.data;

import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.apache.http.HttpResponse;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.DefaultHttpClient;
import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

import at.rueckgr.android.ipwe.CommonData;

// TODO rename Status -> state?
public class Status {
//	private static final String TAG = "Status";
	private List<Sensor> sensors;
	private CommonData commonData;

	public Status() {
		commonData = CommonData.getInstance();
	}
	
	public void update() {
		
		// TODO configurable URL
		// String url = "https://rueckgr.at/sensors/api/?action=status";
		String url = commonData.getSettingsURL() + "?action=status";
		
		// TODO error handling
		DefaultHttpClient httpClient = new DefaultHttpClient();
		HttpGet httpGet = new HttpGet(url);
		HttpResponse httpResponse;
		try {
			httpResponse = httpClient.execute(httpGet);
		} catch (IllegalStateException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
		} catch (ClientProtocolException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
		}
		InputStream inputStream;
		try {
			inputStream = httpResponse.getEntity().getContent();
		} catch (IllegalStateException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
		}
		DocumentBuilderFactory documentBuilderFactory = DocumentBuilderFactory.newInstance();
		DocumentBuilder documentBuilder;
		try {
			documentBuilder = documentBuilderFactory.newDocumentBuilder();
		} catch (ParserConfigurationException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
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
						// TODO duplicate <sensors> element
					}
					nodeProcessed = true;
					processNode(node);
				}
			}
		} catch (SAXException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
		// TODO Auto-generated method stub
		
	}

	private void processNode(Node parentNode) {
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
}
