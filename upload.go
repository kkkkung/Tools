package main

import (
	"bytes"
	"context"
	"database/sql"
	"fmt"
	_ "github.com/go-sql-driver/mysql"
	"github.com/qiniu/api.v7/auth/qbox"
	"github.com/qiniu/api.v7/storage"
	"io"
	"io/ioutil"
	"math/rand"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"
)


// 七牛云设置
const (
	accessKey = ""
	secretKey = ""
	bucket = ""
	per = ""
	baseURL string = ""
)

// 数据库配置
const (
	userName = "root"
	password = ""
	ip = "localhost"
	port = "3306"
	dbName = "mall.rdo"
)

// 自定义返回值结构体
type MyPutRet struct {
	Key    string
	Hash   string
	Name   string
}

// goods_tmp 返回数据结构体
type GoodsTmp struct {
	id int
	productId string
	cover string
	carousel string
	details string
}

// 数据库资源
var DB *sql.DB

func main(){
	//
	//runtime.GOMAXPROCS(4)

	path := strings.Join([]string{userName, ":", password, "@tcp(",ip, ":", port, ")/", dbName, "?charset=utf8"}, "")
	DB, err := sql.Open("mysql", path)
	checkError(err)
	defer DB.Close()

	selectSql := "SELECT `id`,`productId`,`cover`,`carousel`,`details` FROM `goods_tmp` ORDER BY `id` ASC "

	rows, err := DB.Query(selectSql)
	checkErr(err)

	for rows.Next(){

		var selectD GoodsTmp

		// 抓取数据
		err = rows.Scan(&selectD.id, &selectD.productId, &selectD.cover, &selectD.carousel, &selectD.details)
		checkErr(err)
		carouselArr := splitStr(selectD.carousel)
		detailsArr := splitStr(selectD.details)
		var carouselNew []string
		var detailsNew []string

		// 上传封面
		cover := qiNiuUpload(getImageData(selectD.cover))

		// 上传图集
		for _, url := range carouselArr{
			url = strings.Trim(url, "\"")
			curl := qiNiuUpload(getImageData(url))
			carouselNew = append(carouselNew, curl)
		}

		// 上传详细
		for _, url := range detailsArr{
			url = strings.Trim(url, "\"")
			durl := qiNiuUpload(getImageData(url))
			detailsNew = append(detailsNew, durl)
		}

		// 格式数据
		carousel := spliceStrArr(carouselNew)
		details := spliceStrArr(detailsNew)

		//  测试上传后的整理数据
		//fmt.Println(cover)
		//fmt.Println(carousel)
		//fmt.Println(details)

		updateSql := "UPDATE `goods_1` SET `cover` = ?, `carousel` = ?, `details` = ? WHERE `id` = ?;"
		stmt, err := DB.Prepare(updateSql)
		checkErr(err)
		res, err := stmt.Exec(cover, carousel, details, selectD.id)
		checkErr(err)
		affect, err := res.RowsAffected()
		checkErr(err)


		fmt.Printf("Goods id :%d\t was updated\t%d rows affected\n",selectD.id,affect)


	}

}
// 检验连接数据库
func InitDB() {
	//构建连接："用户名:密码@tcp(IP:端口)/数据库?charset=utf8"
	path := strings.Join([]string{userName, ":", password, "@tcp(",ip, ":", port, ")/", dbName, "?charset=utf8"}, "")

	//打开数据库,前者是驱动名，所以要导入： _ "github.com/go-sql-driver/mysql"
	DB, _ = sql.Open("mysql", path)

	//验证连接
	if err := DB.Ping(); err != nil{
		fmt.Println("open database fail")
		return
	}
	fmt.Println("connect success")

}

// 通过URL获取图片数据
func getImageData(url string) (data []byte) {
	imageRequest, err := http.Get(url)
	if checkError(err) { return }
	data, err = ioutil.ReadAll(imageRequest.Body)
	defer imageRequest.Body.Close()
	if checkError(err) { return }

	return data
}

// 通过URL访问图片保存到本地
func getImageByUrl(url string) {
	//reg, err := regexp.Compile("/.*jpg")
	//if checkError(err) { return }
	//image := reg.FindString(url)
	imageRequest, err := http.Get(url)
	if checkError(err) { return }

	data, err := ioutil.ReadAll(imageRequest.Body)
	defer imageRequest.Body.Close()
	if checkError(err) { return }

	name := "image.jpg"
	dir := "images"
	path, _ := filepath.Abs(filepath.Dir(os.Args[0]))

	err = os.Mkdir(path + string(os.PathSeparator) + dir, os.ModeType)
	if checkError(err) { return }

	out, err := os.Create(path + string(os.PathSeparator) + dir + string(os.PathSeparator) + name)
	if checkError(err) { return }

	_, err = io.Copy(out, bytes.NewReader(data))
	if checkError(err) { return }

	fmt.Println("Done")
	}

// 数据流上传文件到七牛云
func qiNiuUpload(data []byte) (url string){
	putPolicy := storage.PutPolicy{
		Scope: bucket,
	}
	mac := qbox.NewMac(accessKey, secretKey)
	upToken := putPolicy.UploadToken(mac)

	cfg := storage.Config{}
	// 空间对应的机房
	cfg.Zone = &storage.ZoneHuanan
	// 是否使用https域名
	cfg.UseHTTPS = true
	// 上传是否使用CDN上传加速
	cfg.UseCdnDomains = true

	formUploader := storage.NewFormUploader(&cfg)
	ret := MyPutRet{}
	putExtra := storage.PutExtra{
		Params: map[string]string{
			"x:name": "test image file",
		},
	}


	key := per + randSeq(8)
	dataLen := int64(len(data))
	err := formUploader.Put(context.Background(), &ret, upToken, key, bytes.NewReader(data), dataLen, &putExtra)
	if checkError(err) { return }
	url = baseURL + ret.Key

	return url
}

// 讲数据库里的格式的图集和详细字符的URL提取出来
func splitStr(s string)(strArr []string){
	s = strings.TrimFunc(s, func(r rune) bool {
		return r == '[' || r == ']'
	})
	if  strings.ContainsRune(s, ','){
		strArr = strings.Split(s, ",")
	} else {
		strArr = append(strArr, s)
	}

	return strArr
}

// 将字符数组格式成数据表中需求存储的格式
func spliceStrArr(arr []string)(s string) {
	s = strings.Join(arr,"\",\"")
	s = "[\"" + s + "\"]"
	return s
}

func randSeq(n int) string {
	rand.Seed(time.Now().UnixNano())
	chars := []rune("ABCDEFGHIJKLMNOPQRSTUVWXYZ" +
		"abcdefghijklmnopqrstuvwxyz" +
		"0123456789")
	var b strings.Builder
	for i := 0; i < n; i++ {
		b.WriteRune(chars[rand.Intn(len(chars))])
	}
	str := b.String()
	return str
}

// 检查错误并打印返回bool
func checkError(err error) bool {
	if err != nil {
		fmt.Println(err)
		return true
	}
	return false
}

// 检查错误并答应错误然后中止程序
func checkErr(err error) {
	if err != nil {
		panic(err)
	}
}
