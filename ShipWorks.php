<?php

class ShipWorks extends FulfillmentInterface
{
	function executeRouting($post)
	{
		$action = $post->action;
		
		if($action == 'getmodule')
		{
			echo $this->getModule();
		}
		elseif($action == 'getstore')
		{
			echo $this->getStore();
		}
		elseif($action == 'getstatuscodes')
		{
			echo $this->getStatusCodes($post); 
		}
		elseif($action == 'getcount')
		{
			echo $this->getCount($post);
		}
		elseif($action == 'getorders')
		{
			echo $this->getOrders($post);
		}
		elseif($action == 'updatestatus')
		{
			echo $this->updateStatus($post);	
		}
		elseif($action == 'updateshipment')
		{
			echo $this->updateShipment($post);	
		}
	}
	
	function getModule()
	{
		return' 
		<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0">
		<Module>
				<Platform></Platform>
				<Developer>Brian Beal</Developer>
			<Capabilities>
				<DownloadStrategy>ByModifiedTime</DownloadStrategy>
				<OnlineCustomerID supported="true" dataType="numeric" />
				<OnlineStatus supported="true" dataType="text" supportsComments="false" downloadOnly="false" />
				<OnlineShipmentUpdate supported="true" />
			</Capabilities>
			</Module>
		</ShipWorks>';
	}
	
	function getStore()
	{
		return '
		<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0">
			<Store>
				<Name></Name>
				<CompanyOrOwner>ContactSales</CompanyOrOwner>
				<Email></Email>
				<Street1>69 Drive</Street1>
				<City>Laketown</City>
				<State>MI</State>
				<PostalCode>48283</PostalCode>
				<Country>US</Country>
				<Phone>1-800-493-2838</Phone>
				<Website></Website>
			</Store>
		</ShipWorks>';
	}
	
	function getStatusCodes($post)
	{
		return '
		<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0">
			<StatusCodes>
				<StatusCode>HOLD</StatusCode>
				<StatusCode>PENDING</StatusCode>
				<StatusCode>DELIVERED</StatusCode>
				<StatusCode>CANCELLED</StatusCode>
				<StatusCode>RETURNED</StatusCode>
				<StatusCode>FAILED</StatusCode>
				<StatusCode>RMA_PENDING</StatusCode>
			</StatusCodes>
		</ShipWorks>';
	}
	
	function utcToLocal(DateTime $date)
	{
		$offset = $date->getOffset();
		$offset = $offset / 3600;
		$isNeg = abs($offset) == $offset ? false : true;
		$modifyStr = ($isNeg ? "-" : "+").abs($offset)." hours";
		$date = $date->modify($modifyStr);
		return $date;
	}
	
	function getCount($post)
	{
		$sql ="
		SELECT COUNT(*) 
		FROM fulfillments F
		WHERE F.dateUpdated	> ?
		";
	
		$start = $post->start;
		$date = new DateTime($start);
		$date = $this->utcToLocal($date);

		$app = Application::getInstance();
		$server = $app->getClientServer();
		$orderCount = $server->fetchValue($sql, $date);
				
		return '<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0"><OrderCount>'.$orderCount.'</OrderCount></ShipWorks>';
	}
	
	function getOrders($post)
	{
		$sql ="
		SELECT fulfillmentId 
		FROM fulfillments F
		WHERE F.dateUpdated	> ?
		";
			
		$start = $post->start;
		$date = new DateTime($start);
		$date = $this->utcToLocal($date);
		
		$app = App::getInstance();
		$server = $app->getClientServer();
		$rs = $server->fetchResultSet($sql, $date);
		
		$xmlString = '';
	
		foreach($rs as $row)
		{
			$fulfillmentId = $row->fulfillmentId;
			
			$fulfillment = Fulfillment::fetch((int)$fulfillmentId);
	
			$order = Order::fetch((int) $fulfillment->orderId);
			
			extract((array) $order);
			extract((array) $fulfillment);
			
			$oitems = $order->items;
			$oitems = arrays::indexByKey($oitems,'orderItemId');
			$fitems = $fulfillment->items;
			
			$itemsStr = '';
			$usedOrderItemIds = array();
			
			foreach($fitems as $item)
			{
				if(!in_array($item->orderItemId,$usedOrderItemIds))
				{
					$unitPrice = $oitems[$item->orderItemId]->price;
					$usedOrderItemIds[] = $item->orderItemId;
				}
				else
					$unitPrice = 0.00;	 
	
				$itemsStr .="
				<Item>
					<Name>$item->name</Name>
					<SKU>$item->sku</SKU>
					<Code></Code>
					<Quantity>$item->qty</Quantity>
					<UnitPrice>$unitPrice</UnitPrice>
					<Weight>5</Weight>
				</Item>";	
			}
						
			$orderDate = new DateTime($dateCreated);
			$lastModified = new DateTime($dateUpdated);
	
			$xmlString .="
			<Order>
				<OrderNumber>$fulfillmentId</OrderNumber>
				<Notes>
					<Note>The real orderId is $clientOrderId</Note>
				</Notes>
				<OrderDate>".$orderDate->format("c")."</OrderDate>
				<LastModified>".$lastModified->format("c")."</LastModified>
				<ShippingMethod>$shipMethod</ShippingMethod>
				<CustomerID>$customerId</CustomerID>
				<StatusCode>$status</StatusCode>
				<Totals>
					<Total>$totalAmount</Total>
				</Totals>
				<ShippingAddress>
					<FullName>$firstName $lastName</FullName>
					<Street1>$shipAddress1</Street1>
					<Street2>$shipAddress2</Street2>
					<City>$shipCity</City>
					<State>$shipState</State>
					<PostalCode>$shipPostalCode</PostalCode>
					<Country>$shipCountry</Country>
					<Phone>$phoneNumber</Phone>
					<Email>$emailAddress</Email>
				</ShippingAddress>						
				<BillingAddress>
					<FullName>$firstName $lastName</FullName>
					<Street1>$address1</Street1>
					<Street2>$address2</Street2>
					<City>$city</City>
					<State>$state</State>
					<PostalCode>$postalCode</PostalCode>
					<Country>$country</Country>
					<Phone>$phoneNumber</Phone>
					<Email>$emailAddress</Email>
				</BillingAddress>
				<Items>
					$itemsStr
				</Items>
			</Order>";
		}
		return '<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0"><Orders>'.$xmlString.'</Orders></ShipWorks>';
	}
	
	function updateStatus($post)
	{
		return '
		<?xml version="1.0" standalone="yes" ?>
		<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0">
			<UpdateSuccess/>
		</ShipWorks>';
	} 
	
	function updateShipment($post)
	{
		$fulfillmentId = $post->order;
		$shipCarrier = $post->carrier;
		$dateShipped = new DateTime($post->shippingdate);
		$trackingNumber = $post->tracking;
		
		if(empty($trackingNumber))
			throw new Exception("No tracking number");
			
		if(!$dateShipped instanceof DateTime)
			throw new Exception("date time doesn't make sense for date shipped");

		$this->updateFulfillmentTracking($fulfillmentId,$trackingNumber,$dateShipped,$shipCarrier);

		return '
		<?xml version="1.0" standalone="yes" ?>
		<ShipWorks moduleVersion="3.0.1" schemaVersion="1.0.0">
			<UpdateSuccess/>
		</ShipWorks>';
	}
}
