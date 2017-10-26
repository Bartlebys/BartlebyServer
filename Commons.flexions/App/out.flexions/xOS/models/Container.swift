//
//  Container.swift
//  Bartleby
//
// THIS FILE AS BEEN GENERATED BY BARTLEBYFLEXIONS for [Benoit Pereira da Silva] (https://pereira-da-silva.com/contact)
// DO NOT MODIFY THIS FILE YOUR MODIFICATIONS WOULD BE ERASED ON NEXT GENERATION!
//
// Copyright (c) 2016  [Bartleby's org] (https://bartlebys.org)   All rights reserved.
//
import Foundation
#if !USE_EMBEDDED_MODULES
#endif

// MARK: Bartleby's Synchronized File System: A container to store Boxes,Nodes,Blocks
@objc open class Container : UnManagedModel {

    // DeclaredTypeName support
    override open class func typeName() -> String {
        return "Container"
    }


	//You can setup a password
	@objc dynamic open var password:String?

	//Boxes
	@objc dynamic open var boxes:[Box] = [Box]()

	//Nodes
	@objc dynamic open var nodes:[Node] = [Node]()

	//Blocks
	@objc dynamic open var blocks:[Block] = [Block]()


    // MARK: - Codable


    public enum ContainerCodingKeys: String,CodingKey{
		case password
		case boxes
		case nodes
		case blocks
    }

    required public init(from decoder: Decoder) throws{
		try super.init(from: decoder)
        try self.quietThrowingChanges {
			let values = try decoder.container(keyedBy: ContainerCodingKeys.self)
			self.password = try values.decodeIfPresent(String.self,forKey:.password)
			self.boxes = try values.decode([Box].self,forKey:.boxes)
			self.nodes = try values.decode([Node].self,forKey:.nodes)
			self.blocks = try values.decode([Block].self,forKey:.blocks)
        }
    }

    override open func encode(to encoder: Encoder) throws {
		try super.encode(to:encoder)
		var container = encoder.container(keyedBy: ContainerCodingKeys.self)
		try container.encodeIfPresent(self.password,forKey:.password)
		try container.encode(self.boxes,forKey:.boxes)
		try container.encode(self.nodes,forKey:.nodes)
		try container.encode(self.blocks,forKey:.blocks)
    }


    // MARK: - Exposed (Bartleby's KVC like generative implementation)

    /// Return all the exposed instance variables keys. (Exposed == public and modifiable).
    override  open var exposedKeys:[String] {
        var exposed=super.exposedKeys
        exposed.append(contentsOf:["password","boxes","nodes","blocks"])
        return exposed
    }


    /// Set the value of the given key
    ///
    /// - parameter value: the value
    /// - parameter key:   the key
    ///
    /// - throws: throws an Exception when the key is not exposed
    override  open func setExposedValue(_ value:Any?, forKey key: String) throws {
        switch key {
            case "password":
                if let casted=value as? String{
                    self.password=casted
                }
            case "boxes":
                if let casted=value as? [Box]{
                    self.boxes=casted
                }
            case "nodes":
                if let casted=value as? [Node]{
                    self.nodes=casted
                }
            case "blocks":
                if let casted=value as? [Block]{
                    self.blocks=casted
                }
            default:
                return try super.setExposedValue(value, forKey: key)
        }
    }


    /// Returns the value of an exposed key.
    ///
    /// - parameter key: the key
    ///
    /// - throws: throws Exception when the key is not exposed
    ///
    /// - returns: returns the value
    override  open func getExposedValueForKey(_ key:String) throws -> Any?{
        switch key {
            case "password":
               return self.password
            case "boxes":
               return self.boxes
            case "nodes":
               return self.nodes
            case "blocks":
               return self.blocks
            default:
                return try super.getExposedValueForKey(key)
        }
    }
    // MARK: - Initializable
     required public init() {
        super.init()
    }
}